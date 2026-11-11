<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Guide;
use App\Models\Method;
use App\Models\VideoCache;
use App\Services\YouTubeService;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Validator;
use function Core\{slugify, url};

/** Staff dashboard and guide management (admins and editors). */
class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        return $this->view('admin/dashboard', [
            'title'  => 'Dashboard',
            'user'   => Session::user(),
            'stats'  => [
                'total'     => Guide::count(),
                'published' => Guide::count('status', 'published'),
                'drafts'    => Guide::count('status', 'draft'),
                'methods'   => Method::count(),
            ],
            'recent' => Guide::listForAdmin(5),
        ], 'admin');
    }

    // ---- list / forms ----

    public function guideIndex(Request $request): Response
    {
        return $this->view('admin/guides/index', [
            'title'  => 'Guides',
            'guides' => Guide::listForAdmin(),
        ], 'admin');
    }

    public function guideCreate(Request $request): Response
    {
        return $this->guideForm('New guide', url('/admin/guides'), [], []);
    }

    public function guideEdit(Request $request): Response
    {
        $guide = $this->findGuide($request);
        if ($guide === null) {
            return Response::error(404);
        }
        return $this->guideForm(
            'Edit guide',
            url("/admin/guides/{$guide['id']}/update"),
            $guide,
            Guide::steps((int) $guide['id'])
        );
    }

    private function guideForm(string $title, string $action, array $guide, array $steps): Response
    {
        // After a failed save, show what the user typed instead of the stored steps.
        $steps = Session::old('steps', $steps);
        if (!is_array($steps) || $steps === []) {
            $steps = [['instruction' => '', 'duration_seconds' => '', 'pour_g' => '']];
        }

        // The video currently attached (or typed before a failed save), with its saved title if we have one.
        $videoId = YouTubeService::extractId((string) Session::old('youtube_video_id', $guide['youtube_video_id'] ?? ''));

        return $this->view('admin/guides/form', [
            'title'              => $title,
            'action'             => $action,
            'guide'              => $guide,
            'steps'              => $steps,
            'methods'            => Method::allOrdered(),
            'currentVideo'       => $videoId ? (VideoCache::find($videoId) ?? ['video_id' => $videoId]) : null,
            'videoSearchEnabled' => (new YouTubeService())->isConfigured(),
        ], 'admin');
    }

    // ---- save ----

    public function guideStore(Request $request): Response
    {
        return $this->saveGuide($request, null);
    }

    public function guideUpdate(Request $request): Response
    {
        $guide = $this->findGuide($request);
        if ($guide === null) {
            return Response::error(404);
        }
        return $this->saveGuide($request, $guide);
    }

    private function saveGuide(Request $request, ?array $existing): Response
    {
        $input = $this->readGuideInput($request);
        $errors = $this->validateGuide($input, $existing ? (int) $existing['id'] : null);
        $back = $existing ? url("/admin/guides/{$existing['id']}/edit") : url('/admin/guides/create');

        if ($errors !== []) {
            Session::flashInput($input);
            Session::flashErrors($errors);
            Session::flash('error', 'Please fix the highlighted problems.');
            return $this->redirect($back);
        }

        $data = [
            'method_id'     => (int) $input['method_id'],
            'title'         => $input['title'],
            'slug'          => $input['slug'],
            'summary'       => $input['summary'] !== '' ? $input['summary'] : null,
            'difficulty'    => $input['difficulty'],
            'coffee_g'      => round((float) $input['coffee_g'], 1),
            'water_g'       => round((float) $input['water_g'], 1),
            'temperature_c' => (int) $input['temperature_c'],
            'status'        => $input['status'],
            'youtube_video_id' => $input['youtube_video_id'] !== '' ? $input['youtube_video_id'] : null,
        ];
        $steps = array_map(fn (array $s): array => [
            'instruction'      => $s['instruction'],
            'duration_seconds' => $s['duration_seconds'] !== '' ? (int) $s['duration_seconds'] : null,
            'pour_g'           => $s['pour_g'] !== '' ? round((float) $s['pour_g'], 1) : null,
        ], $input['steps']);

        // Look the video up once (videos.list) and save its details. A video YouTube says
        // doesn't exist is rejected; if YouTube can't be reached, the link is kept anyway.
        $videoNote = '';
        if ($data['youtube_video_id'] !== null) {
            $youtube = new YouTubeService();
            $videoNote = $this->cacheVideo($data['youtube_video_id'], $youtube);
            if ($youtube->lastError() === 'not_found') {
                Session::flashInput($input);
                Session::flashErrors(['youtube_video_id' => $youtube->errorMessage()]);
                Session::flash('error', 'Please fix the highlighted problems.');
                return $this->redirect($back);
            }
        }

        Guide::saveWithSteps($existing ? (int) $existing['id'] : null, $data, $steps, (int) Session::user()['id']);

        Session::flash('success', ($existing ? 'Guide updated.' : 'Guide created.') . $videoNote);
        return $this->redirect(url('/admin/guides'));
    }

    /** Trim everything, build the slug, and drop completely empty step rows. */
    private function readGuideInput(Request $request): array
    {
        $input = [];
        foreach (['title', 'slug', 'method_id', 'difficulty', 'summary', 'coffee_g', 'water_g', 'temperature_c', 'status', 'youtube_video_id'] as $field) {
            $value = $request->body[$field] ?? '';
            $input[$field] = is_string($value) ? trim($value) : '';
        }
        $input['slug'] = slugify($input['slug'] !== '' ? $input['slug'] : $input['title']);
        // Accept a full YouTube link as well as a bare video ID.
        $input['youtube_video_id'] = YouTubeService::extractId($input['youtube_video_id']) ?? $input['youtube_video_id'];

        $steps = [];
        $rows = $request->body['steps'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $step = [];
                foreach (['instruction', 'duration_seconds', 'pour_g'] as $field) {
                    $value = $row[$field] ?? '';
                    $step[$field] = is_string($value) ? trim($value) : '';
                }
                if ($step['instruction'] === '' && $step['duration_seconds'] === '' && $step['pour_g'] === '') {
                    continue;
                }
                $steps[] = $step;
            }
        }
        $input['steps'] = $steps;
        return $input;
    }

    private function validateGuide(array $input, ?int $exceptId): array
    {
        $v = Validator::make($input)
            ->required('title', 'Title')->maxLength('title', 'Title', 150)
            ->required('slug', 'Slug')->maxLength('slug', 'Slug', 170)
            ->required('method_id', 'Brewing method')
            ->inList('difficulty', 'Difficulty', Guide::DIFFICULTIES)
            ->maxLength('summary', 'Summary', 2000)
            ->number('coffee_g', 'Coffee dose', 1, 1000)
            ->number('water_g', 'Water', 1, 20000)
            ->integer('temperature_c', 'Temperature', 60, 100)
            ->inList('status', 'Status', Guide::STATUSES);

        if ($input['slug'] !== '' && Guide::existsWhere('slug', $input['slug'], $exceptId)) {
            $v->addError('slug', 'That slug is already used by another guide.');
        }
        if ($input['youtube_video_id'] !== '' && !YouTubeService::isValidId($input['youtube_video_id'])) {
            $v->addError('youtube_video_id', 'Enter a YouTube link or an 11-character video ID.');
        }
        if ($input['method_id'] !== '' && (!ctype_digit($input['method_id']) || Method::find((int) $input['method_id']) === null)) {
            $v->addError('method_id', 'Choose a valid brewing method.');
        }

        foreach ($input['steps'] as $i => $step) {
            $n = $i + 1;
            if ($step['instruction'] === '') {
                $v->addError('steps', "Step $n: the instruction is required.");
            } elseif (mb_strlen($step['instruction']) > 2000) {
                $v->addError('steps', "Step $n: the instruction is too long (2000 characters max).");
            }
            if ($step['duration_seconds'] !== ''
                && filter_var($step['duration_seconds'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 3600]]) === false) {
                $v->addError('steps', "Step $n: seconds must be a whole number from 0 to 3600.");
            }
            if ($step['pour_g'] !== ''
                && (!is_numeric($step['pour_g']) || (float) $step['pour_g'] < 0 || (float) $step['pour_g'] > 10000)) {
                $v->addError('steps', "Step $n: pour must be a number from 0 to 10000.");
            }
        }
        if ($input['status'] === 'published' && $input['steps'] === []) {
            $v->addError('steps', 'Add at least one step before publishing.');
        }

        return $v->errors();
    }

    /**
     * Save the video's title, channel and thumbnail once (videos.list), so public pages never call YouTube.
     * Returns an extra sentence for the success message when the details could not be loaded.
     */
    private function cacheVideo(string $videoId, YouTubeService $youtube): string
    {
        if (VideoCache::isFresh($videoId, (int) Config::get('services.youtube.cache_days', 7))) {
            return '';
        }

        $video = $youtube->details($videoId);
        if ($video === null) {
            return ' The video is attached, but its title could not be loaded. ' . $youtube->errorMessage();
        }
        VideoCache::store($video);
        return '';
    }

    // ---- delete / publish ----

    public function guideDelete(Request $request): Response
    {
        $guide = $this->findGuide($request);
        if ($guide === null) {
            return Response::error(404);
        }
        Guide::delete((int) $guide['id']); // its steps are removed by the database (ON DELETE CASCADE)
        Session::flash('success', 'Guide "' . $guide['title'] . '" deleted.');
        return $this->redirect(url('/admin/guides'));
    }

    public function guideToggle(Request $request): Response
    {
        $guide = $this->findGuide($request);
        if ($guide === null) {
            return Response::error(404);
        }

        $newStatus = $guide['status'] === 'published' ? 'draft' : 'published';
        if ($newStatus === 'published' && Guide::stepCount((int) $guide['id']) === 0) {
            Session::flash('error', 'Add at least one step before publishing this guide.');
            return $this->redirect(url('/admin/guides'));
        }

        Guide::update((int) $guide['id'], ['status' => $newStatus]);
        Session::flash('success', 'Guide "' . $guide['title'] . '" is now ' . $newStatus . '.');
        return $this->redirect(url('/admin/guides'));
    }

    private function findGuide(Request $request): ?array
    {
        $id = (string) $request->param('id', '');
        return ctype_digit($id) ? Guide::find((int) $id) : null;
    }
}
