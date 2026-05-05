<?php

use Illuminate\Support\Facades\File;

test('scramble exports documented api controller response codes', function () {
    $path = storage_path('framework/testing-api.json');
    File::delete($path);

    $this->artisan('scramble:export', ['--path' => $path])
        ->assertSuccessful();

    $document = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);

    $operations = [
        ['/orders', 'get', [200, 401, 405, 422, 500]],
        ['/orders', 'post', [200, 401, 405, 422, 500]],
        ['/orders/{order}', 'get', [200, 401, 405, 422, 500]],
        ['/orders/{order}/fulfill', 'post', [200, 401, 405, 422, 500]],
        ['/orders/{order}/status', 'patch', [200, 401, 405, 422, 500]],
        ['/orders/{order}/refunds', 'post', [200, 401, 405, 422, 500]],
        ['/products', 'get', [200, 401, 405, 422, 500]],
        ['/products', 'post', [200, 401, 405, 422, 500]],
        ['/products/{product}', 'patch', [200, 401, 405, 422, 500]],
        ['/products/{product}', 'delete', [204, 401, 405, 422, 500]],
        ['/reports', 'get', [200, 401, 405, 422, 500]],
    ];

    foreach ($operations as [$uri, $method, $statuses]) {
        $responses = array_map('strval', array_keys($document['paths'][$uri][$method]['responses'] ?? []));

        expect($responses)->toContain(...array_map('strval', $statuses));
    }

    File::delete($path);
});
