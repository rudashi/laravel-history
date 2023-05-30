<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Observers;

use DateTimeInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use JsonException;
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
        $this->saveHistory(
            model: $model,
            action: __FUNCTION__,
            meta: $this->setMeta($model, [...$model->excludedHistoryAttributes(), 'uuid', 'id'])
        );
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

    private function saveHistory(HasHistoryInterface|Model $model, string $action, array $meta = null): void
    {
        if (in_array($action, $model->excludedHistoryModelEvents(), true) === false) {
            $this->history->setCustomOwnerKey($model->getLocalKeyName())
                ->fill(['action' => $action, 'meta' => $meta])
                ->model()->associate($model)
                ->user()->associate($this->auth->user())
                ->save();
        }
    }

    private function setMeta(HasHistoryInterface $model, array $exclude = null): array
    {
        $changed = [];
        $exclude = $exclude ?? $model->excludedHistoryAttributes();

        foreach ($model->getDirty() as $attribute => $value) {
            if (in_array($attribute, $exclude, true)) {
                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $value = (string) $model->getAttribute($attribute);
            }

            $changed[] = [
                'key' => $attribute,
                'old' => $this->castAttribute($model->getRawOriginal($attribute)),
                'new' => $this->castAttribute($value),
            ];
        }

        return $changed;
    }

    private function castAttribute(mixed $value = null)
    {
        if (! is_string($value)) {
            return $value;
        }

        try {
            return json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }
    }
}
