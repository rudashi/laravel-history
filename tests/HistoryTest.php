<?php

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;
use Rudashi\LaravelHistory\HistoryServiceProvider;
use Rudashi\LaravelHistory\Listeners\AuthenticationListeners;
use Rudashi\LaravelHistory\Models\History;
use Rudashi\LaravelHistory\Traits\HasHistory;
use Tests\CreatesApplication;

class HistoryTest extends TestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    private FakeUser $user;

    protected function refreshInMemoryDatabase(): void
    {
        $this->app->register(HistoryServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => __DIR__ . '/../src/database/migrations/',
            '--realpath' => __DIR__ . '/../src/database/migrations/'
        ])->run();

        Schema::create('users', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('password');
        });

        Schema::create('messages', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->softDeletes();
        });

        $this->app[Kernel::class]->setArtisan(null);

        $this->user = $this->createUser();
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
        self::assertEquals(MessageSoftDelete::class, $history->model_type);

        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);

        self::assertNotNull($history->model);
        self::assertInstanceOf(MessageSoftDelete::class, $history->model);

        self::assertNotNull($history->user);
        self::assertInstanceOf(FakeUser::class, $history->user);
    }

    public function test_model_has_history(): void
    {
        $message = $this->createModel();
        /** @var History $history */
        $history = $message->history->first();

        self::assertCount(1, $message->history);
        self::assertEquals('created', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(1, $history->meta);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_history_register_create_and_delete_model(): void
    {
        $message = $this->createAndRemoveModel(Message::class);
        $history = History::query()->whereMorphedTo('model', $message)->get();
        /** @var History $deleted */
        $deleted = $history->get(1);

        self::assertCount(2, $history);

        self::assertEquals('created', $message->history->get(0)->action);
        self::assertEquals('deleted', $deleted->action);
        self::assertIsArray($deleted->meta);
        self::assertCount(0, $deleted->meta);
        self::assertEquals($this->user->getKey(), $deleted->user_id);
        self::assertEquals(FakeUser::class, $deleted->user_type);
    }

    public function test_history_register_create_and_soft_delete_model(): void
    {
        $message = $this->createAndRemoveModel();
        /** @var History $history */
        $history = $message->history->get(1);

        self::assertCount(2, $message->history);

        self::assertEquals('created', $message->history->get(0)->action);
        self::assertEquals('deleted', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(0, $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_history_register_model_restore(): void
    {
        $message = $this->createAndRemoveModel();
        $message->restore();
        $message->refresh();

        /** @var History $history */
        $history = $message->history->get(2);

        self::assertCount(3, $message->history);
        self::assertEquals('created', $message->history->get(0)->action);
        self::assertEquals('deleted', $message->history->get(1)->action);
        self::assertEquals('restored', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(0, $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_can_disable_history_for_model(): void
    {
        $message = new Message(['title' => 'title at creating']);
        $message->disableHistory();
        $message->save();

        /** @var History $history */
        self::assertCount(0, $message->history);
    }

    public function test_history_register_model_update(): void
    {
        $message = $this->createModel();
        $message->update([
            'title' => 'updating title'
        ]);
        $message->refresh();

        /** @var History $history */
        $history = $message->history->get(1);

        self::assertCount(2, $message->history);
        self::assertEquals('created', $message->history->get(0)->action);
        self::assertEquals('updated', $history->action);
        self::assertIsArray($history->meta);
        self::assertEquals([['key' => 'title', 'old' => 'title at creating', 'new' => 'updating title']], $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_history_not_register_excluded_attribute(): void
    {
        $message = $this->createModel(MessageWithoutTitle::class);
        $message->update([
            'title' => 'updating title',
            'description' => 'updating desc'
        ]);
        $message->refresh();

        /** @var History $created */
        $created = $message->history->get(0);
        /** @var History $updated */
        $updated = $message->history->get(1);

        self::assertCount(2, $message->history);

        self::assertEquals('created', $created->action);
        self::assertIsArray($created->meta);
        self::assertCount(0, $created->meta);
        self::assertEquals([], $created->meta);

        self::assertEquals('updated', $updated->action);
        self::assertIsArray($updated->meta);
        self::assertCount(1, $updated->meta);
        self::assertEquals([['key' => 'description', 'old' => null, 'new' => 'updating desc']], $updated->meta);
    }

    public function test_history_not_register_excluded_events(): void
    {
        $message = $this->createModel(MessageWithoutEvent::class);
        $message->update([
            'description' => 'updating desc'
        ]);
        $message->refresh();

        /** @var History $history */
        $history = $message->history->first();

        self::assertCount(1, $message->history);
        self::assertEquals('updated', $history->action);
        self::assertIsArray($history->meta);
        self::assertCount(1, $history->meta);
        self::assertEquals([['key' => 'description', 'old' => null, 'new' => 'updating desc']], $history->meta);
        self::assertEquals($this->user->getKey(), $history->user_id);
        self::assertEquals(FakeUser::class, $history->user_type);
    }

    public function test_user_has_registered_history_action(): void
    {
        $message = $this->createModel();

        $user_history = $this->user->operations->first();

        self::assertEquals(1, $this->user->operations->count());
        self::assertEquals('created', $user_history->action);
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $user_history->meta);
        self::assertEquals($message->getKey(), $user_history->model_id);
        self::assertEquals(MessageSoftDelete::class, $user_history->model_type);
    }

    public function test_user_has_no_registered_history_actions(): void
    {
        self::assertEquals(0, $this->user->operations->count());
    }

    public function test_get_model_history(): void
    {
        $message = $this->createModel();
        $message->update(['title' => 'updating title']);
        $history = History::ofModel($message);

        self::assertCount(2, $history);
        self::assertEquals('created', $history->get(0)->action);
        self::assertEquals('updated', $history->get(1)->action);
        self::assertEquals($message->getAttribute('id'), $history->get(0)->model_id);
        self::assertEquals($message->getAttribute('id'), $history->get(1)->model_id);
    }

    public function test_get_user_history(): void
    {
        $message = $this->createModel();
        $message->update(['title' => 'updating title']);
        $history = History::ofUser($this->user::class, $this->user->getAttribute('id'));

        self::assertCount(2, $history);
        self::assertEquals('created', $history->get(0)->action);
        self::assertEquals('updated', $history->get(1)->action);
        self::assertEquals($this->user->getAttribute('id'), $history->get(0)->user_id);
        self::assertEquals($this->user->getAttribute('id'), $history->get(1)->user_id);
    }

    public function test_can_register_authentication_event(): void
    {
        Event::fake();

        Auth::login($this->user);

        Event::assertListening(Login::class, AuthenticationListeners::class);
        Event::assertDispatched(Login::class);
    }

    public function test_can_register_login_event(): void
    {
        Auth::login($this->user);

        $history = History::ofUser($this->user);
        $login = $history->get(0);

        self::assertCount(1, $history);
        self::assertEquals('Login', $login->action);
        self::assertIsArray($login->meta);
        self::assertCount(0, $login->meta);
        self::assertNull($login->model_id);
        self::assertNull($login->model_type);
    }

    public function test_can_register_logout_event(): void
    {
        Auth::logout();

        $history = History::ofUser($this->user);
        $login = $history->get(0);

        self::assertCount(1, $history);
        self::assertEquals('Logout', $login->action);
        self::assertIsArray($login->meta);
        self::assertCount(0, $login->meta);
        self::assertNull($login->model_id);
        self::assertNull($login->model_type);
    }

    private function createAndRemoveModel(string $class = MessageSoftDelete::class): Message|MessageSoftDelete
    {
        $message = $this->createModel($class);
        self::assertCount(1, $message->history);

        $message->delete();
        $message->refresh();

        return $message;
    }

    private function createModel(string $class = MessageSoftDelete::class): Message
    {
        return (new $class)->query()->create([
            'title' => 'title at creating'
        ]);
    }

    private function createUser(): FakeUser|Model
    {
        return FakeUser::query()->create(['name' => 'Monica', 'password' => 'secret']);
    }

}

class Message extends Model implements HasHistoryInterface
{
    use HasHistory;

    public $timestamps = false;
    protected $table = 'messages';
    protected $fillable = ['title', 'description'];
}

class MessageSoftDelete extends Message
{
    use SoftDeletes;
}

class MessageWithoutTitle extends Message
{
    public function excludedHistoryAttributes(): array
    {
        return [ 'title' ];
    }

}

class MessageWithoutEvent extends Message
{
    public function excludedHistoryModelEvents(): array
    {
        return [ 'created' ];
    }

}
