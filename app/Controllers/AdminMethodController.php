<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Guide;
use App\Models\Method;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;
use Core\Validator;
use function Core\{slugify, url};

/** Brewing methods (admins only — see 'role:admin' in routes/web.php). */
class AdminMethodController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('admin/methods/index', [
            'title'   => 'Brewing methods',
            'methods' => Method::allWithGuideCounts(),
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        return $this->form('New brewing method', url('/admin/methods'), []);
    }

    public function edit(Request $request): Response
    {
        $method = $this->findMethod($request);
        if ($method === null) {
            return Response::error(404);
        }
        return $this->form('Edit brewing method', url("/admin/methods/{$method['id']}/update"), $method);
    }

    private function form(string $title, string $action, array $method): Response
    {
        return $this->view('admin/methods/form', [
            'title'  => $title,
            'action' => $action,
            'method' => $method,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        $method = $this->findMethod($request);
        if ($method === null) {
            return Response::error(404);
        }
        return $this->save($request, $method);
    }

    private function save(Request $request, ?array $existing): Response
    {
        $input = [];
        foreach (['name', 'slug', 'description'] as $field) {
            $value = $request->body[$field] ?? '';
            $input[$field] = is_string($value) ? trim($value) : '';
        }
        $input['slug'] = slugify($input['slug'] !== '' ? $input['slug'] : $input['name']);
        $exceptId = $existing ? (int) $existing['id'] : null;

        $v = Validator::make($input)
            ->required('name', 'Name')->maxLength('name', 'Name', 80)
            ->required('slug', 'Slug')->maxLength('slug', 'Slug', 100)
            ->maxLength('description', 'Description', 1000);
        if ($input['name'] !== '' && Method::existsWhere('name', $input['name'], $exceptId)) {
            $v->addError('name', 'A method with that name already exists.');
        }
        if ($input['slug'] !== '' && Method::existsWhere('slug', $input['slug'], $exceptId)) {
            $v->addError('slug', 'That slug is already used by another method.');
        }

        if ($v->fails()) {
            Session::flashInput($input);
            Session::flashErrors($v->errors());
            Session::flash('error', 'Please fix the highlighted problems.');
            return $this->redirect($existing
                ? url("/admin/methods/{$existing['id']}/edit")
                : url('/admin/methods/create'));
        }

        $data = [
            'name'        => $input['name'],
            'slug'        => $input['slug'],
            'description' => $input['description'] !== '' ? $input['description'] : null,
        ];
        if ($existing) {
            Method::update((int) $existing['id'], $data);
        } else {
            Method::create($data);
        }

        Session::flash('success', $existing ? 'Method updated.' : 'Method created.');
        return $this->redirect(url('/admin/methods'));
    }

    public function delete(Request $request): Response
    {
        $method = $this->findMethod($request);
        if ($method === null) {
            return Response::error(404);
        }
        if (Guide::count('method_id', (int) $method['id']) > 0) {
            Session::flash('error', 'Cannot delete "' . $method['name'] . '": guides still use it.');
            return $this->redirect(url('/admin/methods'));
        }
        Method::delete((int) $method['id']);
        Session::flash('success', 'Method "' . $method['name'] . '" deleted.');
        return $this->redirect(url('/admin/methods'));
    }

    private function findMethod(Request $request): ?array
    {
        $id = (string) $request->param('id', '');
        return ctype_digit($id) ? Method::find((int) $id) : null;
    }
}
