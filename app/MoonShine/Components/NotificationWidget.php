<?php

namespace App\MoonShine\Components;

use MoonShine\Laravel\MoonShineAuth;
use MoonShine\UI\Components\MoonShineComponent;

class NotificationWidget extends MoonShineComponent
{
    protected string $view = 'components.notification-widget';

    protected function viewData(): array
    {
        $readAllUrl = rescue(fn (): string => route('moonshine.notifications.readAll'), '#', report: false);
        $empty = ['groups' => [], 'unreadCount' => 0, 'readAllUrl' => $readAllUrl];

        return rescue(function () use ($readAllUrl, $empty) {
            $user = MoonShineAuth::getGuard()->user();

            if (! $user) {
                return $empty;
            }

            $raw = $user->notifications()->latest()->limit(30)->get();

            $buckets = ['Сегодня' => [], 'Вчера' => [], 'Ранее' => []];
            $unreadCount = 0;

            foreach ($raw as $notification) {
                $isUnread = $notification->read_at === null;
                if ($isUnread) {
                    $unreadCount++;
                }

                $created = $notification->created_at;
                $bucket = $created->isToday() ? 'Сегодня' : ($created->isYesterday() ? 'Вчера' : 'Ранее');

                $buckets[$bucket][] = [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? 'info',
                    'title' => $this->title($notification),
                    'description' => $this->description($notification),
                    'time' => $bucket === 'Ранее' ? $created->format('d.m.Y') : $created->format('H:i'),
                    'is_unread' => $isUnread,
                ];
            }

            $groups = [];
            foreach ($buckets as $label => $items) {
                if ($items !== []) {
                    $groups[] = ['label' => $label, 'items' => $items];
                }
            }

            return ['groups' => $groups, 'unreadCount' => $unreadCount, 'readAllUrl' => $readAllUrl];
        }, $empty, report: false);
    }

    private function title(object $notification): string
    {
        $message = (string) ($notification->data['message'] ?? '');
        $pos = mb_strpos($message, '. ');

        return $pos === false ? $message : mb_substr($message, 0, $pos);
    }

    private function description(object $notification): string
    {
        $message = (string) ($notification->data['message'] ?? '');
        $pos = mb_strpos($message, '. ');
        $text = $pos === false ? '' : e(mb_substr($message, $pos + 2));

        foreach (($notification->data['links'] ?? []) as $index => $link) {
            $url = (string) ($link['url'] ?? '#');

            if (! preg_match('#^(https?://|/)#i', $url)) {
                $url = '#';
            }

            $html = sprintf(
                '<a href="%s" class="pacs-link">%s</a>',
                htmlspecialchars($url),
                htmlspecialchars((string) ($link['label'] ?? ''))
            );
            $text = str_replace('{link_' . $index . '}', $html, $text);
        }

        return $text;
    }
}
