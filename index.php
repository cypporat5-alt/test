<?php
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/clients.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$clients = json_decode(file_get_contents($dataFile), true);
if (!is_array($clients)) {
    $clients = [];
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['client_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $wish = trim($_POST['wish'] ?? '');
    $details = trim($_POST['details'] ?? '');

    if ($name === '') {
        $errors[] = 'Укажите имя клиента.';
    }

    if ($wish === '') {
        $errors[] = 'Добавьте пожелание клиента.';
    }

    if (!$errors) {
        $clientKey = mb_strtolower($name);
        if (!isset($clients[$clientKey])) {
            $clients[$clientKey] = [
                'name' => $name,
                'contact' => $contact,
                'wishes' => [],
            ];
        } elseif ($contact !== '') {
            $clients[$clientKey]['contact'] = $contact;
        }

        $clients[$clientKey]['wishes'][] = [
            'text' => $wish,
            'details' => $details,
            'created_at' => date('Y-m-d H:i'),
        ];

        $encoded = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $fileHandle = fopen($dataFile, 'c+');
        if ($fileHandle) {
            flock($fileHandle, LOCK_EX);
            ftruncate($fileHandle, 0);
            fwrite($fileHandle, $encoded);
            fflush($fileHandle);
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
            $success = 'Пожелание сохранено.';
        } else {
            $errors[] = 'Не удалось сохранить данные.';
        }
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Карточки клиентов</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 24px;
            color: #1d1d1f;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin-top: 0;
        }
        form {
            display: grid;
            gap: 16px;
            margin-bottom: 32px;
        }
        label {
            font-weight: 600;
        }
        input, textarea {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #ccd2da;
            font-size: 14px;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 15px;
            cursor: pointer;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
        }
        .alert.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .alert.success {
            background: #dcfce7;
            color: #15803d;
        }
        .grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            background: #f8fafc;
        }
        .wish {
            margin: 12px 0;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .wish small {
            display: block;
            color: #6b7280;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Карточка клиента</h1>
    <p>Быстрая запись пожеланий клиента — заполните форму, и пожелание сохранится в карточке клиента.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <div><?= escape($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($success): ?>
        <div class="alert success"><?= escape($success) ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label for="client_name">Имя клиента</label>
            <input id="client_name" name="client_name" placeholder="Например, Анна Смирнова" required>
        </div>
        <div>
            <label for="contact">Контакт (телефон, email)</label>
            <input id="contact" name="contact" placeholder="+7 999 123-45-67">
        </div>
        <div>
            <label for="wish">Пожелание клиента</label>
            <input id="wish" name="wish" placeholder="Что важно учесть?" required>
        </div>
        <div>
            <label for="details">Детали / комментарий</label>
            <textarea id="details" name="details" placeholder="Детали заказа, предпочтения, ограничения"></textarea>
        </div>
        <button type="submit">Сохранить пожелание</button>
    </form>

    <h2>Список клиентов</h2>
    <div class="grid">
        <?php if (!$clients): ?>
            <p>Пока нет сохранённых клиентов.</p>
        <?php else: ?>
            <?php foreach ($clients as $client): ?>
                <div class="card">
                    <strong><?= escape($client['name']) ?></strong>
                    <?php if (!empty($client['contact'])): ?>
                        <div><?= escape($client['contact']) ?></div>
                    <?php endif; ?>
                    <?php foreach ($client['wishes'] as $wishItem): ?>
                        <div class="wish">
                            <div><?= escape($wishItem['text']) ?></div>
                            <?php if (!empty($wishItem['details'])): ?>
                                <div><?= escape($wishItem['details']) ?></div>
                            <?php endif; ?>
                            <small><?= escape($wishItem['created_at']) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
