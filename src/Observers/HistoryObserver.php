<?php

namespace Rudashi\LaravelHistory\Observers;

use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;
use Rudashi\LaravelHistory\Models\History;

class HistoryObserver
{

    public function created(HasHistoryInterface $model): void
    {
        $this->saveHistory($model, __FUNCTION__);
    }

    public function updated(HasHistoryInterface $model): void
    {
        $changes = $model->getDirty();

        if (array_key_exists(config('laravel-history.DELETED_AT'), $changes)) {
            return;
        }

        $changed = [];
        foreach ($changes as $attribute => $value) {
            $changed[] = ['key' => $attribute, 'old' => $model->getOriginal($attribute), 'new' => $value];
        }

        $this->saveHistory($model, __FUNCTION__, $changed);
    }

    public function deleted(HasHistoryInterface $model): void
    {
        $this->saveHistory($model, __FUNCTION__);
    }

    public function restored(HasHistoryInterface $model): void
    {
        $this->saveHistory($model, __FUNCTION__);
    }

    private function setUser(): array
    {
        return auth()->user() ? [
            'user_id' => auth()->id(),
            'user_type' => get_class(auth()->user()),
        ]: [];
    }

    private function saveHistory(HasHistoryInterface $model, string $action, array $meta = null): void
    {
        $model->history()->save(new History([
                'action' => $action,
                'meta' => $meta,
            ] + $this->setUser()));
    }

}