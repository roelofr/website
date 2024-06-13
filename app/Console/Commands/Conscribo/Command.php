<?php

declare(strict_types=1);

namespace App\Console\Commands\Conscribo;

use App\Services\ConscriboService;
use Illuminate\Console\Command as BaseCommand;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand implements Isolatable
{
    protected function downloadAllForEntityType(ConscriboService $conscriboService, string $entityType): Collection
    {
        $listFieldsResponse = $conscriboService->call('listFieldDefinitions', [
            'entityType' => $entityType,
        ]);

        $fields = Collection::make($listFieldsResponse['fields'])
            ->pluck('fieldName')
            ->values()
            ->all();

        return Collection::make($conscriboService->call('listRelations', [
            'entityType' => $entityType,
            'requestedFields' => [
                'fieldName' => $fields,
            ],
        ])['relations'] ?? []);
    }

    /**
     * Override to ensure we're always running isolated.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->setOption('isolated', true);

        parent::initialize($input, $output);
    }
}
