<?php

namespace Blacky0892\Max;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class MaxApiClient
{
    /**
     * Создаёт и возвращает базовый HTTP-клиент для работы с API MAX.
     *
     * Клиент инициализируется на основании параметров из конфигурации пакета:
     * - max.token — токен авторизации бота;
     * - max.base_url — базовый URL API MAX;
     * - max.timeout — максимальное время ожидания ответа;
     * - max.connect_timeout — максимальное время на установку соединения;
     * - max.retry_times — количество повторных попыток при ошибке;
     * - max.retry_sleep — задержка между повторами в миллисекундах.
     *
     * Метод используется как единая точка создания HTTP-клиента для всех
     * запросов к API, чтобы поведение клиента было консистентным во всём пакете.
     *
     * @return PendingRequest Настроенный экземпляр HTTP-клиента Laravel.
     */
    public function http(): PendingRequest
    {
        $request = Http::withHeaders([
            'Authorization' => config('max.token'),
            'Accept' => 'application/json',
        ])
            ->baseUrl(rtrim(config('max.base_url'), '/'))
            ->timeout((int) config('max.timeout', 30))
            ->connectTimeout((int) config('max.connect_timeout', 10));

        $retryTimes = (int) config('max.retry_times', 0);
        $retrySleep = (int) config('max.retry_sleep', 200);

        if ($retryTimes > 0) {
            $request = $request->retry($retryTimes, $retrySleep);
        }

        return $request;
    }

    /**
     * Выполняет GET-запрос к API MAX.
     *
     * Используется для получения данных без передачи тела запроса.
     * Параметры передаются в query string.
     *
     * @param string $uri Относительный URI метода API, например: /me или /messages.
     * @param array<string, mixed> $query Query-параметры запроса.
     *
     * @return array Декодированный JSON-ответ API в виде массива.
     */
    public function get(string $uri, array $query = []): array
    {
        return $this->send('get', $uri, query: $query);
    }

    /**
     * Выполняет POST-запрос к API MAX.
     *
     * Используется для создания сущностей, отправки сообщений, запросов на загрузку
     * файлов и других операций, требующих передачи JSON-тела.
     *
     * @param string $uri Относительный URI метода API.
     * @param array<string, mixed> $data JSON-тело запроса.
     * @param array<string, mixed> $query Query-параметры запроса.
     *
     * @return array Декодированный JSON-ответ API в виде массива.
     */
    public function post(string $uri, array $data = [], array $query = []): array
    {
        return $this->send('post', $uri, data: $data, query: $query);
    }

    /**
     * Выполняет PUT-запрос к API MAX.
     *
     * Обычно используется для обновления существующих сущностей, например,
     * для редактирования уже отправленного сообщения.
     *
     * @param string $uri Относительный URI метода API.
     * @param array<string, mixed> $data JSON-тело запроса.
     * @param array<string, mixed> $query Query-параметры запроса.
     *
     * @return array Декодированный JSON-ответ API в виде массива.
     */
    public function put(string $uri, array $data = [], array $query = []): array
    {
        return $this->send('put', $uri, data: $data, query: $query);
    }

    /**
     * Выполняет DELETE-запрос к API MAX.
     *
     * Используется для удаления сообщений и других сущностей API, поддерживающих
     * соответствующий HTTP-метод.
     *
     * @param string $uri Относительный URI метода API.
     * @param array<string, mixed> $data JSON-тело запроса.
     * @param array<string, mixed> $query Query-параметры запроса.
     *
     * @return array Декодированный JSON-ответ API в виде массива.
     */
    public function delete(string $uri, array $data = [], array $query = []): array
    {
        return $this->send('delete', $uri, data: $data, query: $query);
    }

    /**
     * Универсальный метод отправки HTTP-запроса к API MAX.
     *
     * Внутренний low-level метод, через который проходят все публичные методы
     * клиента. Формирует итоговый URL с query string, отправляет JSON-тело
     * и выбрасывает исключение при HTTP-ошибке.
     *
     * @param string $method HTTP-метод запроса: get, post, put, delete.
     * @param string $uri Относительный URI метода API.
     * @param array<string, mixed> $data JSON-тело запроса.
     * @param array<string, mixed> $query Query-параметры запроса.
     *
     * @return array Декодированный JSON-ответ API либо пустой массив,
     * если тело ответа отсутствует.
     * @throws RequestException|ConnectionException Если API вернуло ошибочный HTTP-статус.
     *
     */
    protected function send(
        string $method,
        string $uri,
        array $data = [],
        array $query = [],
    ): array {
        /** @var Response $response */
        $response = $this->http()->send(
            strtoupper($method),
            $uri . ($query ? '?' . http_build_query($query) : ''),
            [
                'json' => $data,
            ]
        )->throw();

        return $response->json() ?? [];
    }

    /**
     * Возвращает информацию о текущем боте / приложении MAX.
     *
     * Обычно используется для проверки токена, базовой диагностики подключения
     * и получения сведений о текущем авторизованном клиенте.
     *
     * @return array Ответ API MAX с информацией о текущем пользователе/боте.
     */
    public function getMe(): array
    {
        return $this->get('/me');
    }

    /**
     * Отправляет сообщение в чат по его идентификатору.
     *
     * Это обёртка над sendMessage(), автоматически формирующая получателя
     * через параметр chat_id.
     *
     * @param int|string $chatId Идентификатор чата.
     * @param string|null $text Текст сообщения.
     * @param array<int, array<string, mixed>> $attachments Вложения сообщения
     * в формате API MAX.
     * @param string|null $format Формат текста сообщения, если поддерживается API.
     * @param bool|null $notify Нужно ли отправлять уведомление получателю.
     * @param bool|null $disableLinkPreview Нужно ли отключить предпросмотр ссылок.
     * @param array<string, mixed>|null $link Дополнительный объект ссылки, если поддерживается API.
     *
     * @return array Ответ API MAX с результатом отправки сообщения.
     */
    public function sendMessageToChat(
        int|string $chatId,
        ?string $text = null,
        array $attachments = [],
        ?string $format = null,
        ?bool $notify = null,
        ?bool $disableLinkPreview = null,
        ?array $link = null,
    ): array {
        return $this->sendMessage(
            ['chat_id' => $chatId],
            $text,
            $attachments,
            $format,
            $notify,
            $disableLinkPreview,
            $link,
        );
    }

    /**
     * Отправляет личное сообщение пользователю по его идентификатору.
     *
     * Это обёртка над sendMessage(), автоматически формирующая получателя
     * через параметр user_id.
     *
     * @param int|string $userId Идентификатор пользователя MAX.
     * @param string|null $text Текст сообщения.
     * @param array<int, array<string, mixed>> $attachments Вложения сообщения
     * в формате API MAX.
     * @param string|null $format Формат текста сообщения, если поддерживается API.
     * @param bool|null $notify Нужно ли отправлять уведомление получателю.
     * @param bool|null $disableLinkPreview Нужно ли отключить предпросмотр ссылок.
     * @param array<string, mixed>|null $link Дополнительный объект ссылки, если поддерживается API.
     *
     * @return array Ответ API MAX с результатом отправки сообщения.
     */
    public function sendMessageToUser(
        int|string $userId,
        ?string $text = null,
        array $attachments = [],
        ?string $format = null,
        ?bool $notify = null,
        ?bool $disableLinkPreview = null,
        ?array $link = null,
    ): array {
        return $this->sendMessage(
            ['user_id' => $userId],
            $text,
            $attachments,
            $format,
            $notify,
            $disableLinkPreview,
            $link,
        );
    }

    /**
     * Отправляет пользователю сообщение с inline-кнопкой запроса контакта.
     *
     * Метод формирует клавиатуру с кнопкой request_contact, чтобы пользователь
     * мог отправить свой номер телефона прямо в диалоге с ботом.
     *
     * @param int|string $userId Идентификатор пользователя, у которого запрашивается контакт.
     * @param string|null $text Текст сообщения над кнопкой.
     *
     * @return array Ответ API MAX с результатом отправки сообщения.
     */
    public function requestContact(int|string $userId,
                                   ?string $text = 'Поделитесь контактом',
    ): array {
        $keyboard = [
            [
                'type' => 'inline_keyboard',
                'payload' => [
                    'buttons' => [
                        [
                            [
                                'type' => 'request_contact',
                                'text' => 'Предоставить номер телефона',
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this->sendMessageToUser($userId, $text, $keyboard);
    }

    /**
     * Отправляет сообщение в API MAX указанному получателю.
     *
     * Базовый high-level метод отправки сообщений. Поддерживает отправку:
     * - текста;
     * - вложений;
     * - параметров форматирования;
     * - дополнительных опций уведомления и предпросмотра ссылок.
     *
     * Получатель задаётся массивом вида:
     * - ['chat_id' => ...] для отправки в чат;
     * - ['user_id' => ...] для отправки пользователю.
     *
     * Все null-значения автоматически исключаются из тела запроса.
     *
     * @param array<string, int|string> $recipient Получатель сообщения.
     * @param string|null $text Текст сообщения.
     * @param array<int, array<string, mixed>> $attachments Список вложений в формате API MAX.
     * @param string|null $format Формат текста, если поддерживается API.
     * @param bool|null $notify Нужно ли отправлять уведомление.
     * @param bool|null $disableLinkPreview Нужно ли отключить предпросмотр ссылок.
     * @param array<string, mixed>|null $link Дополнительный объект ссылки.
     *
     * @return array Ответ API MAX с данными отправленного сообщения.
     */
    public function sendMessage(
        array $recipient,
        ?string $text = null,
        array $attachments = [],
        ?string $format = null,
        ?bool $notify = null,
        ?bool $disableLinkPreview = null,
        ?array $link = null,
    ): array {
        $body = array_filter([
            'text' => $text,
            'attachments' => $attachments ?: null,
            'format' => $format,
            'notify' => $notify,
            'disable_link_preview' => $disableLinkPreview,
            'link' => $link,
        ], static fn ($value) => $value !== null);

        return $this->post('/messages', $body, $recipient);
    }

    /**
     * Редактирует ранее отправленное сообщение.
     *
     * Позволяет изменить текст, вложения, форматирование или связанные данные ссылки
     * у уже существующего сообщения.
     *
     * @param int|string $messageId Идентификатор сообщения, которое нужно изменить.
     * @param string|null $text Новый текст сообщения.
     * @param array<int, array<string, mixed>> $attachments Новый список вложений.
     * @param string|null $format Формат текста сообщения.
     * @param array<string, mixed>|null $link Дополнительный объект ссылки.
     *
     * @return array Ответ API MAX с результатом редактирования сообщения.
     */
    public function editMessage(
        int|string $messageId,
        ?string $text = null,
        array $attachments = [],
        ?string $format = null,
        ?array $link = null,
    ): array {
        $body = array_filter([
            'text' => $text,
            'attachments' => $attachments ?: null,
            'format' => $format,
            'link' => $link,
        ], static fn ($value) => $value !== null);

        return $this->put('/messages', $body, [
            'message_id' => $messageId,
        ]);
    }

    /**
     * Удаляет сообщение по его идентификатору.
     *
     * @param int|string $messageId Идентификатор сообщения в системе MAX.
     *
     * @return array Ответ API MAX с результатом удаления.
     */
    public function deleteMessage(int|string $messageId): array
    {
        return $this->delete('/messages', [], [
            'message_id' => $messageId,
        ]);
    }

    /**
     * Получает список сообщений по заданным критериям.
     *
     * Метод поддерживает выборку сообщений:
     * - по chat_id;
     * - по списку идентификаторов message_ids;
     * - по временному диапазону from/to;
     * - с ограничением количества count.
     *
     * Если список $messageIds передан, он преобразуется в строку, разделённую запятыми,
     * в соответствии с ожидаемым форматом query-параметра API.
     *
     * @param int|string|null $chatId Идентификатор чата для выборки сообщений.
     * @param array<int, int|string>|null $messageIds Конкретные идентификаторы сообщений.
     * @param int|null $from Начало диапазона выборки.
     * @param int|null $to Конец диапазона выборки.
     * @param int|null $count Максимальное количество сообщений.
     *
     * @return array Ответ API MAX со списком найденных сообщений.
     */
    public function getMessages(
        int|string|null $chatId = null,
        ?array          $messageIds = null,
        ?int            $from = null,
        ?int            $to = null,
        ?int            $count = null,
    ): array {
        $query = array_filter([
            'chat_id' => $chatId,
            'message_ids' => $messageIds ? implode(',', $messageIds) : null,
            'from' => $from,
            'to' => $to,
            'count' => $count,
        ], static fn ($value) => $value !== null);

        return $this->get('/messages', $query);
    }

    /**
     * Запрашивает у API MAX временный URL для загрузки файла.
     *
     * Обычно процесс загрузки файла двухэтапный:
     * 1. Получить upload URL через API;
     * 2. Загрузить файл по выданному URL напрямую.
     *
     * @param string $type Тип загружаемого объекта, например: file, image, video.
     *
     * @return array Ответ API MAX, содержащий URL и метаданные для загрузки.
     */
    public function requestUploadUrl(string $type = 'file'): array
    {
        return $this->post('/uploads', [], [
            'type' => $type,
        ]);
    }

    /**
     * Загружает файл в MAX по пути в файловой системе.
     *
     * Сначала метод получает временный upload URL через requestUploadUrl(),
     * после чего выполняет multipart-загрузку файла напрямую по выданному адресу.
     *
     * @param string $path Абсолютный или относительный путь к локальному файлу.
     * @param string $type Тип файла для API MAX, например: file, image, video.
     *
     * @throws InvalidArgumentException Если файл по указанному пути не найден.
     * @throws RequestException|ConnectionException Если API вернуло ошибку при загрузке.
     *
     * @return array Ответ сервиса загрузки с токеном или метаданными файла.
     */
    public function uploadFromPath(string $path, string $type = 'file'): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }

        $upload = $this->requestUploadUrl($type);

        return Http::attach('data', fopen($path, 'r'), basename($path))
            ->timeout((int) config('max.timeout', 30))
            ->post($upload['url'])
            ->throw()
            ->json();
    }

    /**
     * Загружает файл в MAX из объекта UploadedFile.
     *
     * Метод предназначен для использования в Laravel-приложениях, когда файл
     * уже получен из HTTP-запроса, например через форму или API endpoint.
     *
     * @param UploadedFile $file Загруженный файл Laravel.
     * @param string $type Тип файла для API MAX, например: file, image, video.
     *
     * @throws RequestException|ConnectionException Если API вернуло ошибку при загрузке.
     *
     * @return array<mixed> Ответ сервиса загрузки с токеном или метаданными файла.
     */
    public function uploadFromUploadedFile(UploadedFile $file, string $type = 'file'): array
    {
        $upload = $this->requestUploadUrl($type);

        return Http::attach(
            'data',
            fopen($file->getRealPath(), 'r'),
            $file->getClientOriginalName()
        )
            ->timeout((int) config('max.timeout', 30))
            ->post($upload['url'])
            ->throw()
            ->json();
    }

    /**
     * Отправляет видео в чат по токену ранее загруженного файла.
     *
     * Метод предполагает, что видео уже было загружено в MAX и для него получен
     * токен, который можно передать как вложение типа video.
     *
     * @param int|string $chatId Идентификатор чата.
     * @param string $videoToken Токен загруженного видеофайла.
     * @param string $caption Подпись к видео.
     * @param string|null $format Формат текста подписи.
     *
     * @return array Ответ API MAX с результатом отправки сообщения с видео.
     */
    public function sendVideoToChat(
        int|string $chatId,
        string $videoToken,
        string $caption = '',
        ?string $format = null,
    ): array {
        return $this->sendMessageToChat(
            $chatId,
            $caption,
            [
                [
                    'type' => 'video',
                    'payload' => [
                        'token' => $videoToken,
                    ],
                ],
            ],
            $format
        );
    }

    /**
     * Отправляет ответ на callback-событие.
     *
     * Используется для подтверждения или обработки callback-запроса, пришедшего
     * от интерактивных элементов интерфейса, например кнопок.
     *
     * Можно передать:
     * - notification — всплывающее уведомление пользователю;
     * - message — сообщение, связанное с ответом на callback.
     *
     * Все null-значения автоматически удаляются из тела запроса.
     *
     * @param string $callbackId Идентификатор callback-события.
     * @param string|null $notification Текст уведомления для пользователя.
     * @param array<string, mixed>|null $message Дополнительное сообщение в формате API MAX.
     *
     * @return array Ответ API MAX с результатом обработки callback.
     */
    public function answerCallback(
        string $callbackId,
        ?string $notification = null,
        ?array $message = null,
    ): array {
        $body = array_filter([
            'callback_id' => $callbackId,
            'notification' => $notification,
            'message' => $message,
        ], static fn ($value) => $value !== null);

        return $this->post('/answers', $body);
    }
}