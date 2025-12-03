<?php
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: uniqid('post-');
}

function flash_message(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $msg;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function load_blocked_words(): array
{
    $path = __DIR__ . '/../config/blocked_words.txt';
    if (!file_exists($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_map(static fn($word) => strtolower(trim($word)), $lines);
}

function find_blocked_words(string $content): array
{
    $blockedWords = load_blocked_words();
    $matches = [];

    foreach ($blockedWords as $blocked) {
        if ($blocked !== '' && stripos($content, $blocked) !== false) {
            $matches[] = $blocked;
        }
    }

    return array_values(array_unique($matches));
}
?>
