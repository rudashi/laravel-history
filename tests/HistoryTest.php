<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Schema;
use Rudashi\LaravelHistory\HistoryServiceProvider;
use Rudashi\LaravelHistory\Models\History;
use Tests\CreatesApplication;

class HistoryTest extends TestCase
{

    use CreatesApplication;
    use RefreshDatabase;

    /**
     * @var \Illuminate\Database\Eloquent\Model|FakeUser
     */
    private $user;

    protected function refreshInMemoryDatabase(): void
    {
        $this->app->register(HistoryServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => __DIR__ . '/../src/database/migrations/',
            '--realpath' => __DIR__ . '/../src/database/migrations/'
        ]);

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('password');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->softDeletes();
        });

        $this->app[Kernel::class]->setArtisan(null);

        $this->user = FakeUser::query()->create(['name' => 'Monica', 'password' => 'secret']);
        $this->actingAs($this->user);
    }

    protected function usingInMemoryDatabase(): bool
    {
        return true;
    }

    public function test_database(): void
    {
        $this->assertDatabaseHas('users', ['name' => 'Monica']);
        $this->assertDatabaseMissing('messages', ['title' => '']);
        $this->assertDatabaseMissing('model_histories', ['action' => '']);
    }

    public function test_history_has_relationships(): void
    {
        $message = $this->createModel();
        $history = $message->history->first();

        self::assertEquals($message->getKey(), $history->model_id);
        self::assertEquals(FakeMessage::class, $history->model_type);

        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);

        self::assertNotNull($history->model);
        self::assertInstanceOf(FakeMessage::class, $history->model);

        self::assertNotNull($history->user);
        self::assertInstanceOf(FakeUser::class, $history->user);
    }

    public function test_model_deleted(): void
    {
        $message = $this->createAndRemoveModel();
        /** @var History $history */
        $history = $message->history->get(1);

        self::assertEquals(2, $message->history->count());
        self::assertEquals('deleted', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(0, $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_model_has_history(): void
    {
        $message = $this->createModel();
        /** @var History $history */
        $history = $message->history->first();

        self::assertEquals(1, $message->history->count());
        self::assertEquals('created', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(1, $history->meta);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_model_restored(): void
    {
        $message = $this->createAndRemoveModel();

        self::assertEquals(2, $message->history->count());

        $message->restore();
        $message->refresh();
        $history = $message->history->get(2);

        self::assertEquals(3, $message->history->count());
        self::assertEquals('restored', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(0, $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_model_update(): void
    {
        $message = $this->createModel();
        $message->history()->delete();

        self::assertEquals(0, $message->history->count());

        $message->update([
            'title' => 'updating title'
        ]);
        $message->refresh();
        $history = $message->history->first();

        self::assertEquals(1, $message->history->count());
        self::assertEquals('updated', $history->action);
        self::assertIsArray($history->meta);
        self::assertEquals([['key' => 'title', 'old' => 'title at creating', 'new' => 'updating title']], $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_user_has_actions(): void
    {
        $message = $this->createModel();

        $user_history = $this->user->operations->first();

        self::assertEquals(1, $this->user->operations->count());
        self::assertEquals('created', $user_history->action);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $user_history->meta);
        self::assertEquals($message->getKey(), $user_history->model_id);
        self::assertEquals(FakeMessage::class, $user_history->model_type);
    }

    public function test_user_has_no_actions(): void
    {
        self::assertEquals(0, $this->user->operations->count());
    }

    private function createAndRemoveModel(): FakeMessage
    {
        $message = $this->createModel();
        self::assertEquals(1, $message->history->count());

        $message->delete();
        $message->refresh();

        return $message;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Model|FakeMessage
     */
    private function createModel(): FakeMessage
    {
        return FakeMessage::query()->create([
            'title' => 'title at creating'
        ]);
    }

}
