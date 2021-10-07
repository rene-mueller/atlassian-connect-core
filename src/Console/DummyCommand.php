<?php

namespace AtlassianConnectCore\Console;

use AtlassianConnectCore\Models\Tenant;
use AtlassianConnectCore\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;

/**
 * Class DummyCommand
 *
 * @package AtlassianConnectCore\Console
 */
class DummyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'connect:dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make your tenant dummy';

    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * InstallCommand constructor.
     *
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        parent::__construct();

        $this->tenantService = $tenantService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = $this->tenantService->findReals();

        $this->table(
            ['ID', 'Client Key (last 22 chars)', 'Product', 'Status', 'Created At'],
            $this->formatTenants($tenants, [
                'id', 'client_key', 'product_type', 'event_type', 'created_at'
            ])
        );

        // Make the list of choices
        $choices = $tenants
            ->pluck('id')
            ->toArray();

        $id = $this->ask('Which the tenant should be dummy? (Pass ID)');

        $validator = Validator::make(['value' => $id], ['value' => Rule::in($choices)]);

        if($validator->fails()) {
            $this->error('Invalid ID provided');
            exit(1);
        }

        $this->tenantService->makeDummy($id);

        $this->comment('Tenant <info>' . $id . '</info> has been set as dummy');
    }

    /**
     * Format given tenants to columns with specific attributes
     *
     * @param Collection|Tenant[] $tenants
     * @param array $attributes Attributes need to be returned
     *
     * @return \Illuminate\Support\Collection
     */
    protected function formatTenants($tenants, array $attributes): \Illuminate\Support\Collection
    {
        $tenants = collect($tenants->toArray());

        return $tenants->map(function ($tenant) use ($attributes) {

            // Make the client key shorter for displaying
            $tenant['client_key'] = substr($tenant['client_key'], -22);

            return Arr::only($tenant, $attributes);
        });
    }
}