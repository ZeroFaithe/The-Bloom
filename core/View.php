<?php
declare(strict_types=1);

namespace Core;

/** Renders app/Views/<name>.php inside app/Views/layouts/<layout>.php */
class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): string
    {
        $viewsDir = BASE_PATH . '/app/Views';
        $content = self::capture("$viewsDir/$view.php", $data);

        if ($layout === '') {
            return $content;
        }
        return self::capture("$viewsDir/layouts/$layout.php", $data + ['content' => $content]);
    }

    private static function capture(string $file, array $data): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $file");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
