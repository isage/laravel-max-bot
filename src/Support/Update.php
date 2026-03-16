<?php

namespace Blacky0892\Max\Support;

class Update
{
    /**
     * Объект-обёртка над входящим payload webhook-события MAX.
     *
     * Класс инкапсулирует доступ к данным входящего обновления и предоставляет
     * удобные accessor/helper-методы для наиболее часто используемых полей:
     * типа события, идентификаторов пользователя и чата, текста сообщения,
     * callback-идентификатора, вложений и других.
     *
     * Основная цель класса — убрать размазанную по обработчикам логику вида
     * data_get($payload, '...') и централизовать знания о том, где именно
     * в разных типах событий MAX может лежать нужное значение.
     *
     * @param array<string, mixed> $payload Исходный payload webhook-события.
     */
    public function __construct(
        protected array $payload,
    ) {
    }

    /**
     * Создаёт новый экземпляр Update из сырого payload-массива.
     *
     * Этот метод полезен для единообразного стиля создания объекта:
     * Update::make($payload)
     *
     * @param array<string, mixed> $payload Исходный payload webhook-события.
     *
     * @return static
     */
    public static function make(array $payload): static
    {
        return new static($payload);
    }

    /**
     * Возвращает исходный payload без изменений.
     *
     * Полезно для:
     * - логирования;
     * - отладки;
     * - передачи исходных данных в пользовательский обработчик;
     * - доступа к редким полям, для которых пока нет отдельного метода.
     *
     * @return array<string, mixed> Полный payload обновления.
     */
    public function raw(): array
    {
        return $this->payload;
    }

    /**
     * Возвращает тип входящего обновления.
     *
     * Метод поддерживает несколько вариантов расположения типа события,
     * так как структура входящих данных может различаться.
     *
     * Приоритет поиска:
     * - update_type
     * - type
     *
     * Примеры ожидаемых значений:
     * - bot_started
     * - message_created
     * - message_callback
     *
     * @return string|null Тип события или null, если он не найден.
     */
    public function type(): ?string
    {
        return $this->get('update_type')
            ?? $this->get('type')
            ?? null;
    }

    /**
     * Возвращает временную метку события.
     *
     * Формат timestamp может зависеть от версии payload и источника события,
     * поэтому возвращаемое значение не ужесточается до int.
     *
     * @return int|string|null Временная метка события или null.
     */
    public function timestamp(): int|string|null
    {
        return $this->get('timestamp') ?? null;
    }

    /**
     * Возвращает идентификатор чата, если событие связано с чатом.
     *
     * Проверяемые пути:
     * - message.chat_id
     * - chat.chat_id
     * - chat_id
     *
     * @return int|string|null Идентификатор чата или null.
     */
    public function chatId(): int|string|null
    {
        return $this->get('message.chat_id')
            ?? $this->get('chat.chat_id')
            ?? $this->get('chat_id')
            ?? null;
    }

    /**
     * Возвращает идентификатор пользователя, связанного с событием.
     *
     * MAX может присылать user_id в разных ветках payload, поэтому метод
     * последовательно проверяет несколько наиболее типичных путей.
     *
     * Проверяемые пути:
     * - user.user_id
     * - from.user_id
     * - sender.user_id
     * - user_id
     * - message.user.user_id
     * - message.from.user_id
     * - message.sender.user_id
     * - message.user_id
     *
     * @return int|string|null Идентификатор пользователя или null.
     */
    public function userId(): int|string|null
    {
        return $this->get('user.user_id')
            ?? $this->get('from.user_id')
            ?? $this->get('sender.user_id')
            ?? $this->get('user_id')
            ?? $this->get('message.user.user_id')
            ?? $this->get('message.from.user_id')
            ?? $this->get('message.sender.user_id')
            ?? $this->get('message.user_id')
            ?? null;
    }

    /**
     * Возвращает полный блок message из payload.
     *
     * Полезно, когда обработчику нужен доступ к полному набору данных сообщения,
     * а не только к отдельным полям.
     *
     * @return array<string, mixed>|null Массив сообщения или null.
     */
    public function message(): ?array
    {
        return $this->get('message') ?? null;
    }

    /**
     * Возвращает список вложений сообщения.
     *
     * На текущий момент метод ориентируется на структуру, где вложения находятся
     * в message.body.attachments.
     *
     * @return array<int, array<string, mixed>>|null Вложения сообщения или null.
     */
    public function attachments(): ?array
    {
        return $this->get('message.body.attachments') ?? null;
    }

    /**
     * Проверяет, содержит ли сообщение хотя бы одно вложение.
     *
     * @return bool true, если список вложений не пуст.
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments());
    }

    /**
     * Возвращает текст сообщения.
     *
     * Метод проверяет несколько возможных мест расположения текста.
     *
     * Приоритет поиска:
     * - message.body.text
     * - message.text
     * - text
     *
     * @return string|null Текст сообщения или null.
     */
    public function text(): ?string
    {
        return $this->get('message.body.text')
            ?? $this->get('message.text')
            ?? $this->get('text')
            ?? null;
    }

    /**
     * Проверяет, содержит ли событие непустой текст сообщения.
     *
     * Строка из одних пробелов считается пустой.
     *
     * @return bool true, если текст существует и не пуст после trim().
     */
    public function hasText(): bool
    {
        $text = $this->text();

        return is_string($text) && trim($text) !== '';
    }

    /**
     * Возвращает идентификатор callback-события.
     *
     * Используется для обработки нажатий на кнопки и прочих интерактивных
     * действий, требующих ответа через endpoint /answers.
     *
     * Проверяемые пути:
     * - callback.id
     * - message_callback.callback_id
     * - payload.callback_id
     *
     * @return string|null Идентификатор callback или null.
     */
    public function callbackId(): ?string
    {
        return $this->get('callback.id')
            ?? $this->get('message_callback.callback_id')
            ?? $this->get('payload.callback_id')
            ?? null;
    }

    /**
     * Возвращает идентификатор сообщения.
     *
     * Проверяемые пути:
     * - message.body.mid
     * - message.mid
     * - message_id
     *
     * @return int|string|null Идентификатор сообщения или null.
     */
    public function messageId(): int|string|null
    {
        return $this->get('message.body.mid')
            ?? $this->get('message.mid')
            ?? $this->get('message_id')
            ?? null;
    }

    /**
     * Возвращает тип чата/получателя сообщения.
     *
     * Примеры значений:
     * - chat
     * - user
     * - channel
     *
     * @return string|null Тип получателя или null.
     */
    public function chatType(): ?string
    {
        return $this->get('message.recipient.chat_type')
            ?? $this->get('recipient.chat_type')
            ?? $this->get('recepient.chat_type')
            ?? null;
    }

    /**
     * Универсальный доступ к данным payload по dot-notation ключу.
     *
     * Это базовый helper-метод класса. Использует Laravel data_get().
     *
     * @param string $key Ключ в формате dot notation.
     * @param mixed $default Значение по умолчанию.
     *
     * @return mixed Найденное значение либо $default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload, $key, $default);
    }

    /**
     * Проверяет, является ли текущее обновление событием запуска бота.
     *
     * @return bool true, если тип события равен bot_started.
     */
    public function isBotStarted(): bool
    {
        return $this->type() === 'bot_started';
    }

    /**
     * Проверяет, является ли текущее обновление событием создания сообщения.
     *
     * @return bool true, если тип события равен message_created.
     */
    public function isMessageCreated(): bool
    {
        return $this->type() === 'message_created';
    }

    /**
     * Проверяет, является ли текущее обновление callback-событием.
     *
     * @return bool true, если тип события равен message_callback.
     */
    public function isMessageCallback(): bool
    {
        return $this->type() === 'message_callback';
    }

    /**
     * Проверяет, пришло ли событие из группового чата.
     *
     * @return bool true, если chat_type равен chat.
     */
    public function isChat(): bool
    {
        return $this->chatType() === 'chat';
    }

    /**
     * Проверяет, является ли событие личным сообщением.
     *
     * Обычно используется, когда бот должен реагировать только на личные
     * сообщения, а события из групповых чатов нужно игнорировать.
     *
     * @return bool true, если chat_type равен user.
     */
    public function isPrivate(): bool
    {
        return $this->chatType() === 'user';
    }

    /**
     * Проверяет, принадлежит ли обновление указанному пользователю.
     *
     * Сравнение выполняется в строковом виде, чтобы избежать лишних проблем
     * из-за различия int/string в payload и вызывающем коде.
     *
     * @param int|string $userId Ожидаемый идентификатор пользователя.
     *
     * @return bool true, если userId совпадает.
     */
    public function isFromUser(int|string $userId): bool
    {
        $actualUserId = $this->userId();

        if ($actualUserId === null) {
            return false;
        }

        return (string) $actualUserId === (string) $userId;
    }

    /**
     * Проверяет, является ли текст сообщения указанной командой.
     *
     * Метод сравнивает текст сообщения после trim() с переданной командой.
     * Подходит для простых сценариев, где команды приходят в виде:
     * /start
     * /help
     * /menu
     *
     * @param string $command Команда для проверки, например: /start
     *
     * @return bool true, если текст сообщения совпадает с командой.
     */
    public function isCommand(string $command): bool
    {
        if (! $this->hasText()) {
            return false;
        }

        return trim((string) $this->text()) === trim($command);
    }
}