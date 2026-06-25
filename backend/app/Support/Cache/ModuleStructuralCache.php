<?php

namespace App\Support\Cache;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class ModuleStructuralCache extends StructuralCacheStore
{
    private const ACTIVE_KEY = 'mystique:modules:active';

    private const ADMIN_LIST_KEY = 'mystique:modules:admin-list';

    public function moduleKey(string $uuid): string
    {
        return "mystique:module:{$uuid}";
    }

    /**
     * @param  callable(): Collection<int, Module>  $resolver
     * @return Collection<int, Module>
     */
    public function rememberActive(callable $resolver): Collection
    {
        return $this->remember(self::ACTIVE_KEY, $resolver);
    }

    /**
     * @param  callable(): Collection<int, Module>  $resolver
     * @return Collection<int, Module>
     */
    public function rememberAdminList(callable $resolver): Collection
    {
        return $this->remember(self::ADMIN_LIST_KEY, $resolver);
    }

    public function rememberModule(string $uuid, callable $resolver): ?Module
    {
        return $this->remember($this->moduleKey($uuid), $resolver);
    }

    public function store(Module $module): void
    {
        $this->put($this->moduleKey($module->uuid), $module);
        $this->forgetLists();
    }

    public function forgetModule(Module $module): void
    {
        $this->forget($this->moduleKey($module->uuid));
        $this->forgetLists();
    }

    public function forgetLists(): void
    {
        $this->forget(self::ACTIVE_KEY, self::ADMIN_LIST_KEY);
    }
}
