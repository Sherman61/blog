<?php
require_once __DIR__ . '/../includes/functions.php';

function admin_nav(string $active = 'dashboard'): void
{
    $items = [
        'dashboard' => ['label' => 'Dashboard', 'href' => site_url('admin/index.php')],
        'posts' => ['label' => 'Posts', 'href' => site_url('admin/posts.php')],
        'categories' => ['label' => 'Categories', 'href' => site_url('admin/categories.php')],
        'comments' => ['label' => 'Comments', 'href' => site_url('admin/comments.php')],
    ];
    echo '<nav class="admin-nav">';
    foreach ($items as $key => $item) {
        $class = $key === $active ? 'admin-nav__link active' : 'admin-nav__link';
        echo '<a class="' . $class . '" href="' . htmlspecialchars($item['href']) . '">';
        echo htmlspecialchars($item['label']);
        echo '</a>';
    }
    echo '</nav>';
}
?>
