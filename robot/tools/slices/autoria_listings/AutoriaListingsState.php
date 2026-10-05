<?php

/**
 * Что уже отправляли.
 *
 * Объект владеет состоянием сам. В первой версии markSeen принимал
 * массив по значению, и отметка молча терялась: каждое объявление
 * приходило заново при каждой проверке. Здесь мутирует сам объект,
 * поэтому вызывающему нечего забыть присвоить.
 */
class AutoriaListingsState
{
    /**
 * Сколько живёт запись.
 *
 * Раньше был месяц. Этого мало: на auto.ria машина спокойно висит в
 * выдаче месяцами, и через 30 дней запись обрезалась, объявление
 * снова попадало в «новые» - и покупатель получал дубль в отчёте.
 * Повторно показывать одно и то же объявление хуже, чем держать файл
 * состояния чуть крупнее: в файл попадают только по-настоящему новые
 * объявления, за год это единицы тысяч записей, то есть меньше мегабайта.
 */
    private const TTL_SECONDS = 31536000;

    private string $path;

    /** @var array<string, mixed> */
    private array $state = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->ensureDir();
        $this->load();
    }

    /**
     * Состояние целиком.
     *
     * Нужно не только самому классу: по состоянию видно, сколько
     * объявлений бот помнит, и его имеет смысл показывать покупателю
     * при разборе - почему то или иное объявление не пришло повторно.
     *
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return $this->state;
    }

    /**
     * Заменить состояние целиком.
     *
     * Нужно там, где состояние правят не через markSeen(): разбор
     * файла состояния вручную, откат к резервной копии, проверки.
     *
     * @param array<string, mixed> $state
     */
    public function setState(array $state): void
    {
        $this->state = $state + ['seen' => [], 'tasks' => []];
    }

    /**
     * Прочитать состояние из файла.
     *
     * Битый файл - не повод падать: лучше прислать объявление
     * повторно, чем не работать вовсе.
     *
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if (!file_exists($this->path)) {
            $this->state = ['seen' => [], 'tasks' => []];
            return $this->state;
        }

        $raw = file_get_contents($this->path);

        if ($raw === false || trim($raw) === '') {
            $this->state = ['seen' => [], 'tasks' => []];
            return $this->state;
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            TOOLS::$log->error('Файл состояния повреждён, начинаем заново: ' . $this->path, __METHOD__);
            $this->state = ['seen' => [], 'tasks' => []];
            return $this->state;
        }

        $this->state = $data + ['seen' => [], 'tasks' => []];
        return $this->state;
    }

    /**
     * Записать состояние атомарно.
     *
     * Сначала во временный файл, потом подменой: прямая запись
     * обрезала файл при сбое питания, и вся история обрывалась.
     * На Windows rename не перезаписывает, поэтому снимаем файл и
     * пробуем снова.
     */
    public function save(): bool
    {
        $json = json_encode(
            $this->state,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            TOOLS::$log->error('Не удалось подготовить состояние к записи', __METHOD__);
            return false;
        }

        $tmp = $this->path . '.tmp';

        if (file_put_contents($tmp, $json) === false) {
            TOOLS::$log->error('Не удалось записать временный файл: ' . $tmp, __METHOD__);
            return false;
        }

        if (!@rename($tmp, $this->path)) {
            if (!@unlink($this->path) || !@rename($tmp, $this->path)) {
                TOOLS::$log->error('Не удалось подменить файл состояния: ' . $this->path, __METHOD__);
                return false;
            }
        }

        return true;
    }

    /**
     * Отметить объявление как обработанное.
     *
     * @return bool false если такое уже отмечали
     */
    public function markSeen(AutoriaListingItem $item): bool
    {
        if ($this->isSeen($item->key())) {
            return false;
        }

        $this->state['seen'][$item->key()] = [
            'url' => $item->url,
            'postedDate' => $item->postedDate,
            'seenAt' => time(),
        ];

        return true;
    }

    /**
     * Приходило ли такое объявление.
     */
    public function isSeen(string $key): bool
    {
        return isset($this->state['seen'][$key]);
    }

    /**
     * Когда задача была создана. Запоминается при первом запуске.
     *
     * @return string Y-m-d
     */
    public function getTaskCreatedAt(string $boardName): string
    {
        $date = $this->state['tasks'][$boardName]['createdAt'] ?? null;

        if (is_string($date) && $date !== '') {
            return $date;
        }

        $today = date('Y-m-d');
        $this->state['tasks'][$boardName] = ['createdAt' => $today];

        return $today;
    }

    /**
     * Убрать старые записи.
     *
     * Без этого файл растёт бесконечно.
     *
     * Возвращает количество удалённых записей, а не пишет в лог:
     * логирование сделало бы класс зависимым от Studio и не позволяло
     * бы проверить его обычным PHP. Сообщение собирает вызывающий.
     *
     * @return int сколько записей убрано
     */
    public function prune(): int
    {
        $cutoff = time() - self::TTL_SECONDS;
        $removed = 0;

        foreach ($this->state['seen'] as $key => $entry) {
            $seenAt = (int)($entry['seenAt'] ?? 0);
            if ($seenAt > 0 && $seenAt < $cutoff) {
                unset($this->state['seen'][$key]);
                $removed++;
            }
        }

        return $removed;
    }

    private function ensureDir(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Не удалось создать папку для состояния: ' . $dir);
        }
    }
}