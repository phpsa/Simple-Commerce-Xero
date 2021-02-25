<?php

namespace Phpsa\StatamicXero;

use Illuminate\Filesystem\Filesystem;

class ConfigStore
{
    /** @var Filesystem  */
    protected $files;


    /** @var string */
    protected $filePath;

    public function __construct(Filesystem $files)
    {
        $this->files         = $files;
        $this->filePath      = storage_path('framework/statamic_xero_mapping.json');
    }

    public function getSalesRevenue(): ?string
    {
        return $this->data('sales_revenue');
    }

    public function getAccountsReceivable(): ?string
    {
        return $this->data('accounts_receivable');
    }

    public function getShippingDelivery(): ?string
    {
        return $this->data('shipping_delivery');
    }

    public function getRounding(): ?string
    {
        return $this->data('rounding');
    }

    public function getDiscounts(): ?string
    {
        return $this->data('discounts');
    }

    public function getAdditionalFees(): ?string
    {
        return $this->data('additional_fees');
    }


    public function getData(): array
    {
        return $this->data() ?? [];
    }

    public function exists(): bool
    {
        return $this->files->exists($this->filePath);
    }

    public function store(array $data): void
    {
        $ret = $this->files->put($this->filePath, json_encode($data));

        if ($ret === false) {
            throw new \Exception("Failed to write to file: {$this->filePath}");
        }
    }

    protected function data($key = null)
    {
        if (! $this->exists()) {
            return null;
        }

        $cacheData = json_decode($this->files->get($this->filePath), true);

        return empty($key) ? $cacheData : ($cacheData[$key] ?? null);
    }
}
