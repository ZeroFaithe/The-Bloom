<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\YouTubeService;
use Core\Controller;
use Core\Request;
use Core\Response;

/** JSON endpoint for the video picker in the guide form (staff only). */
class AdminVideoController extends Controller
{
    public function search(Request $request): Response
    {
        $query = $request->query['q'] ?? '';
        $query = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        if ($query === '') {
            return $this->json(['error' => 'Type what to search for, for example "V60 recipe".'], 422);
        }

        $youtube = new YouTubeService();
        $videos = $youtube->search($query);
        if ($videos === null) {
            $status = $youtube->lastError() === 'not_configured' ? 503 : 502;
            return $this->json(['error' => $youtube->errorMessage()], $status);
        }
        return $this->json(['results' => $videos]);
    }
}
