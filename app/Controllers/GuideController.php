<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Guide;
use App\Models\GuideStep;
use App\Models\Method;
use App\Models\VideoCache;
use App\Services\RecipeScaler;
use App\Services\YouTubeService;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

/** Public pages: the guide list (with filters and search) and a single guide. */
class GuideController extends Controller
{
    public function index(Request $request): Response
    {
        $keyword = mb_substr($this->queryString($request, 'q'), 0, 100);
        $methodSlug = $this->queryString($request, 'method');
        $difficulty = $this->queryString($request, 'difficulty');

        $method = $methodSlug !== '' ? Method::findBy('slug', $methodSlug) : null;
        if (!in_array($difficulty, Guide::DIFFICULTIES, true)) {
            $difficulty = '';
        }

        $guides = Guide::searchPublished(
            $keyword,
            $method ? (int) $method['id'] : null,
            $difficulty !== '' ? $difficulty : null
        );

        return $this->view('guides/index', [
            'title'    => 'Coffee brewing guides',
            'guides'   => $guides,
            'methods'  => Method::allOrdered(),
            'filters'  => [
                'q'          => $keyword,
                'method'     => $method['slug'] ?? '',
                'difficulty' => $difficulty,
            ],
            'filtered' => $keyword !== '' || $method !== null || $difficulty !== '',
        ]);
    }

    public function show(Request $request): Response
    {
        $guide = Guide::findWithMethodBySlug((string) $request->param('slug', ''));
        $isStaff = Session::hasRole('admin', 'editor');

        // Drafts are hidden from visitors; staff can preview them.
        if ($guide === null || ($guide['status'] !== 'published' && !$isStaff)) {
            return Response::error(404);
        }

        $timeline = RecipeScaler::timeline(GuideStep::forGuide((int) $guide['id']));

        // The video plays from its saved ID; the title comes from video_cache. No API call here.
        $video = null;
        $videoId = (string) ($guide['youtube_video_id'] ?? '');
        if (YouTubeService::isValidId($videoId)) {
            $video = VideoCache::find($videoId) ?? ['video_id' => $videoId];
        }

        return $this->view('guides/show', [
            'title'        => $guide['title'],
            'guide'        => $guide,
            'steps'        => $timeline['steps'],
            'totalSeconds' => $timeline['total_seconds'],
            'isPreview'    => $guide['status'] !== 'published',
            'video'        => $video,
        ]);
    }

    private function queryString(Request $request, string $key): string
    {
        $value = $request->query[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}
