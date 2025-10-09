<?php

namespace App\MoonShine\Resources;

use App\Models\BotChat;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Notifications\MoonShineNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BotChatResource extends BaseModelResource
{
    protected string $model = BotChat::class;
    protected string $title = 'Чаты бота';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function isDisplayInNavigation(): bool
    {
        return false;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('ID чата', 'chat_id')
                ->sortable()
                ->badge('blue'),
            Text::make('Название', 'name')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [

            BelongsTo::make('Бот', 'bot', fn($item) => $item->name)
                ->required()
                ->disabled()
                ->default(request('resourceItem')),
            Text::make('ID чата', 'chat_id')
                ->required(),
            Text::make('Название', 'name')
                ->required(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            BelongsTo::make('Бот', 'bot', fn($item) => $item->name),
            Text::make('ID чата', 'chat_id'),
            Text::make('Название', 'name'),
        ];
    }

    // Добавляем кнопку "Тест" в таблицу
    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()
            ->prepend(
                ActionButton::make('Тест', function (Model $item) {
                    $result = $item->sendTestMessage();

                    if ($result['success'] ?? false) {
                        MoonShineNotification::send(
                            message: '✅ ' . $result['message']
                        );
                    } else {
                        MoonShineNotification::send(
                            message: '❌ ' . ($result['message'] ?? 'Ошибка отправки')
                        );
                    }

                    return '';
                })
                    ->withConfirm(
                        title: 'Тестовое сообщение',
                        content: 'Отправить тестовое сообщение в этот чат?',
                        button: 'Отправить',
                    )
                    ->primary()
                    ->icon('play-circle')
            );
    }

    public function rules(mixed $item): array
    {
        return [
            'bot_id' => 'required|exists:bots,id',
            'chat_id' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($item) {
                    if (!BotChat::validateChatId($value)) {
                        $fail('Некорректный формат Chat ID. Допустимо: целое число или отрицательное
                        целое число (для групп), @grouplink - для публичных групп');
                        return;
                    }
                    $botId = $this->getBotIdForValidation($item);

                    if (!$botId) {
                        $fail('Не удалось определить бота');
                        return;
                    }

                    if (BotChat::duplicateChatId($botId, $value, $item?->id)) {
                        $fail('Данный чат уже привзяан к этому боту');
                        return;
                    }
                },
            ],
            'name' => 'required|string|max:255',
        ];
    }

    /**
     * Получить bot_id для валидации
     */
    private function getBotIdForValidation(mixed $item): ?int
    {
        if ($item && $item->exists) {
            return $item->bot_id;
        }

        return request('bot_id') ?? request('resourceItem');
    }

    public function validationMessages(): array
    {
        return [
            'bot_id.required' => 'Бот обязателен',
            'bot_id.exists' => 'Выбранный бот не существует',
            'chat_id.required' => 'Chat ID обязателен',
            'chat_id.string' => 'Chat ID должен быть числом',
            'chat_id.max' => 'Chat ID не должен превышать 255 символов',
            'name.required' => 'Название обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
        ];
    }

    public function customQueryBuilder(?Builder $builder): static
    {
        // Если builder не передан, создаем новый запрос
        $query = $builder ?? $this->getModel()->newQuery();

        // Фильтруем чаты по текущему боту при создании через HasMany
        if (request()->has('resourceItem')) {
            $botId = request('resourceItem');
            $query->where('bot_id', $botId);
        }

        return parent::customQueryBuilder($query);
    }

}
