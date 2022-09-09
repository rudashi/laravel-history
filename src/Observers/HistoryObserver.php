<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Observers;

use Illuminate\Contracts\Auth\Guard;
use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;
use Rudashi\LaravelHistory\Models\History;

class HistoryObserver
{
    public function __construct(
        private readonly History $history,
        private readonly Guard $auth
    ) {
    }

    public function created(HasHistoryInterface $model): void
    {
        $this->saveHistory($model,__FUNCTION__, $this->setMeta($model, [...$model->excludedHistoryAttributes(), 'uuid', 'id']));
    }

    public function deleted(HasHistoryInterface $model): void
    {
        $this->saveHistory($model, __FUNCTION__, $this->setMeta($model));
    }

    public function restored(HasHistoryInterface $model): void
    {
        $this->saveHistory($model, __FUNCTION__, $this->setMeta($model));
    }

    public function updated(HasHistoryInterface $model): void
    {
        if (method_exists($model, 'getDeletedAtColumn') && $model->wasChanged($model->getDeletedAtColumn())) {
            return;
        }

        $this->saveHistory($model, __FUNCTION__, $this->setMeta($model));
    }

    private function saveHistory($model, string $action, array $meta = null): void
    {
        $this->history->fill(['action' => $action, 'meta' => $meta])
            ->model()->associate($model)
            ->user()->associate($this->auth->user())
            ->save();
    }

    private function setMeta(HasHistoryInterface $model, array $exclude = null): array
    {
        $changed = [];
        $exclude = $exclude ?? $model->excludedHistoryAttributes();

        foreach ($model->getDirty() as $attribute => $value) {
            if (in_array($attribute, $exclude, true)) {
                continue;
            }
            $changed[] = ['key' => $attribute, 'old' => $model->getOriginal($attribute), 'new' => $value];
        }

        return $changed;
    }
}
