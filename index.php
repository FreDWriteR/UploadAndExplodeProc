<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload&Explode</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="upload-form-container">
    <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
        <h2>Выберите .txt файл</h2>
        <div class="upload-file-container">
            <label for="fileInput" class="styled-file-button">Обзор</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="204800">
            <input type="file" name="uploadedFile" id="fileInput" accept=".txt" required>
            <div class="js-file-name"></div>
        </div>
        <h2>Введите символ для разбиения</h2>
        <label for="delimiter"></label>
        <input type="text" name="delimiter" id="delimiter" required placeholder="любой символ">
        <div class="submit-file">
            <button type="button" id="submitBtn">Загрузить файл</button>
        </div>
        <div id="uploadStatus"></div>
    </form>
</div>
<div class="explode-container">

    <h2>Разбивка файла</h2>
    <div id="result">

    </div>
</div>
<script>
    document.getElementById('fileInput').addEventListener('change', function(event) {
        let file = event.target.files[0];
        let maxFileSizeKB = 200;
        let maxFileSizeBytes = maxFileSizeKB * 1024;

        if (file && file.size > maxFileSizeBytes) {
            document.getElementById('uploadStatus').innerHTML = '<div class="status error">Размер файла превышает допустимый размер (200KB).</div>';
            event.target.value = '';
            outFileName('');
            return;
        }

        let fileType = file.name.split('.').pop().toLowerCase();
        if (fileType !== 'txt') {
            document.getElementById('uploadStatus').innerHTML = '<div class="status error">Файл должен быть .txt</div>';
            event.target.value = ''; // Очистка поля выбора файла
            outFileName('');
            return;
        }
        document.getElementById('uploadStatus').innerHTML = '';
        outFileName(file.name);

    });

    document.addEventListener('DOMContentLoaded', () => {
        cleanField(document.querySelector('#fileInput'));
        cleanField(document.querySelector('#delimiter'));
        let submitBtn = document.querySelector('#submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                let form = document.getElementById('uploadForm');
                if (form && form.checkValidity()) {
                    let formData = new FormData(form);

                    let xhr = new XMLHttpRequest();
                    xhr.open('POST', 'upload.php', true);

                    xhr.setRequestHeader('Accept', 'application/json');

                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            let response = JSON.parse(xhr.responseText);
                            let resultDiv = document.getElementById('result');
                            let uploadStatus = document.getElementById('uploadStatus');

                            if (response.status === 'success') {

                                let resultHtml = '';
                                response.result.forEach(function (line) {
                                    resultHtml += '<p>' + line + '</p>';
                                });
                                resultDiv.innerHTML = resultHtml;
                                uploadStatus.innerHTML = '<div class="status success">Файл успешно загружен и обработан.</div>';
                            } else {
                                uploadStatus.innerHTML = '<div class="status error">' + response.message + '</div>';
                            }
                        } else {
                            document.getElementById('uploadStatus').innerHTML = '<div class="status error">Ошибка при отправке запроса.</div>';
                        }
                    };

                    xhr.send(formData);
                }
                else {
                    form.reportValidity();
                }
            });
        }
    });

    function outFileName(fileName) {
        let nameField = document.querySelector('.js-file-name');
        if (nameField && fileName) {
            nameField.innerHTML = fileName;
        }
        if (!fileName) {
            nameField.innerHTML = '';
        }
    }

    function cleanField(field) {
        if (field) {
            field.value = '';
        }
    }
</script>

</body>
</html>