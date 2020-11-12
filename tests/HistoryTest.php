<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Rudashi\LaravelHistory\HistoryServiceProvider;
use Tests\CreatesApplication;
use Illuminate\Foundation\Testing\TestCase;

class HistoryTest extends TestCase
{

    use CreatesApplication;
    use RefreshDatabase;

    /**
     * @var FakeUser
     */
    private $user;

    protected function usingInMemoryDatabase(): bool
    {
        return true;
    }

    protected function refreshInMemoryDatabase(): void
    {
        $this->app->register(HistoryServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => __DIR__.'/../src/database/migrations/',
            '--realpath' => __DIR__.'/../src/database/migrations/'
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

        $this->user = FakeUser::create(['name' => 'Monica', 'password' => 'secret']);
        $this->actingAs($this->user);
    }

    public function testDataBase(): void
    {
        $this->assertDatabaseHas('users', ['name' => 'Monica']);
        $this->assertDatabaseMissing('messages', ['title'=>'']);
        $this->assertDatabaseMissing('model_histories', ['action'=>'']);
    }

    public function testModelHasHistory(): void
    {
        $message = $this->createModel();
        $history = $message->history->first();

        self::assertEquals(1, $message->history->count());
        self::assertEquals('created', $history->action);
        self::assertIsArray($history->meta);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $history->meta);
        self::assertEquals($this->user->id, $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function testModelUpdate(): void
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
        self::assertEquals($this->user->id, $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function testModelDeleted(): void
    {
        $message = $this->createModel();
        self::assertEquals(1, $message->history->count());

        $message->delete();
        $message->refresh();

        $history = $message->history->get(1);

        self::assertEquals(2, $message->history->count());
        self::assertEquals('deleted', $history->action);
        self::assertNull($history->meta);
        self::assertEquals($this->user->id, $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function testModelRestored(): void
    {
        $message = $this->createModel();
        self::assertEquals(1, $message->history->count());

        $message->delete();
        $message->refresh();
        self::assertEquals(2, $message->history->count());

        $message->restore();
        $message->refresh();
        $history = $message->history->get(2);

        self::assertEquals(3, $message->history->count());
        self::assertEquals('restored', $history->action);
        self::assertNull($history->meta);
        self::assertEquals($this->user->id, $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function testUserHasNoActions(): void
    {
        self::assertEquals(0, $this->user->operations->count());
    }

    public function testUserHasActions(): void
    {
        $message = $this->createModel();

        $user_history = $this->user->operations->first();

        self::assertEquals(1, $this->user->operations->count());
        self::assertEquals('created', $user_history->action);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $user_history->meta);
        self::assertEquals($message->id, $user_history->model_id);
        self::assertEquals(FakeMessage::class, $user_history->model_type);
    }

    public function testHistoryHasRelationships(): void
    {
        $message = $this->createModel();
        $history = $message->history->first();

        self::assertEquals($message->id, $history->model_id);
        self::assertEquals(FakeMessage::class, $history->model_type);

        self::assertEquals($this->user->id, $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);

        self::assertNotNull($history->model);
        self::assertInstanceOf(FakeMessage::class, $history->model);

        self::assertNotNull($history->user);
        self::assertInstanceOf(FakeUser::class, $history->user);
    }

    private function createModel(): FakeMessage
    {
        return FakeMessage::create([
            'title' => 'title at creating'
        ]);
    }

}