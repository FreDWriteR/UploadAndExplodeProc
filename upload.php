<?php
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['uploadedFile'])) {
        $uploadDir = __DIR__ . '/files/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось создать папку для загрузки.']);
                exit;
            }
        }
        
        $uploadedFile = $_FILES['uploadedFile'];
        $fileName = basename($uploadedFile['name']);
        $targetFilePath = $uploadDir . $fileName;

        // Получаем символ для разделения от пользователя
        $delimiter = $_POST['delimiter'];

        if (strlen($delimiter) !== 1) {
            echo json_encode(['status' => 'error', 'message' => 'Разделитель должен быть одним символом.']);
            exit;
        }
        
        // Получение максимального размера файла из php.ini
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $uploadMaxFilesizeBytes = parse_size($uploadMaxFilesize);

        // Получение максимального размера данных POST-запроса из php.ini
        $postMaxSize = ini_get('post_max_size');
        $postMaxSizeBytes = parse_size($postMaxSize);


        // Определение кодов ошибок загрузки
        $errorCodes = [
            UPLOAD_ERR_OK => 'Файл загружен успешно.',
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает максимальный размер,<br>предусмотренный сервером (php.ini).',
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает значение, указанное<br>в атрибуте MAX_FILE_SIZE формы.',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично.',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен.',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка.',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск.',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла прервана расширением PHP.'
        ];

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = isset($errorCodes[$uploadedFile['error']]) ? $errorCodes[$uploadedFile['error']] : 'Неизвестная ошибка загрузки.';
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
            exit;
        }

        // Проверка размера загружаемого файла
        if ($uploadedFile['size'] > $uploadMaxFilesizeBytes) {
            echo json_encode(['status' => 'error', 'message' => 'Размер файла превышает максимальный размер, предусмотренный сервером (php.ini).']);
            exit;
        }

        // Проверка, на превышение размера данных POST-запроса
        if ($uploadedFile['size'] > $postMaxSizeBytes) {
            echo json_encode(['status' => 'error', 'message' => 'Размер файла превышает максимальный размер данных POST-запроса (php.ini).']);
            exit;
        }

        // Проверка расширения файла
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        if ($fileType !== 'txt') {
            echo json_encode(['status' => 'error', 'message' => 'Файл должен быть .txt']);
            exit;
        }

        // Попытка сохранения файла
        if (move_uploaded_file($uploadedFile['tmp_name'], $targetFilePath)) {
            // Чтение и обработка файла
            $fileContent = file_get_contents($targetFilePath);
            $lines = explode($delimiter, $fileContent);
            $result = [];

            foreach ($lines as $line) {
                $line = trim($line);  // Убираем лишние пробелы
                if ($line !== '') {
                    $digitsCount = preg_match_all('/\d/', $line);  // Количество цифр в строке
                    $result[] = $line . ' = ' . $digitsCount;
                }
            }

            // Возвращаем результат в JSON формате
            echo json_encode(['status' => 'success', 'result' => $result]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при сохранении файла. Возможно,<br>директория недоступна/не существует<br>или у вас нет разрешения на запись.']);
        }
    }

function parse_size($size) {
    $unit = strtolower(substr($size, -1));
    $size = (int) $size;
    switch ($unit) {
        case 'g': $size *= 1024 * 1024 * 1024; break; // Гигабайты
        case 'm': $size *= 1024 * 1024; break; // Мегабайты
        case 'k': $size *= 1024; break; // Килобайты
    }
    return $size;
}