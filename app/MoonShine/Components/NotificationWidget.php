<?php

namespace App\MoonShine\Components;


use MoonShine\UI\Components\MoonShineComponent;

class NotificationWidget extends MoonShineComponent
{
    protected string $view = 'components.notification-widget';

    protected function viewData(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [
                'notifications' => [],
                'unreadCount' => 0,
                'unreadIds' => [],
            ];
        }

        $rawNotifications = $user->notifications()->limit(30)->get();
        $unreadCount = 0;
        $unreadIds = [];
        $notifications = [];

        foreach ($rawNotifications as $notification) {
            if ($notification->read_at === null) {
                $unreadCount++;
                $unreadIds[] = $notification->id;
            }

            $data = $notification->data;
            $text = $data['message'] ?? '';
            $links = $data['links'] ?? [];

            foreach ($links as $index => $link) {
                $html = sprintf(
                    '<a href="%s" class="pacs-link">%s</a>',
                    htmlspecialchars($link['url']),
                    htmlspecialchars($link['label'])
                );
                $text = str_replace('{link_' . $index . '}', $html, $text);
            }

            $notifications[] = [
                'id' => $notification->id,
                'type' => $data['type'] ?? 'info',
                'html_message' => $text,
                'date' => $notification->created_at->format('d.m.Y, H:i'),
            ];
        }

        if ($unreadCount > 0) {
            $user->unreadNotifications->markAsRead();
        }

        return [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'unreadIds' => $unreadIds,
        ];
    }
}
