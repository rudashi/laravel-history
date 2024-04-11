<?php

declare(strict_types=1);

namespace Rudashi\LaravelHistory\Tests;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Rudashi\LaravelHistory\Contracts\HasHistoryInterface;
use Rudashi\LaravelHistory\Listeners\AuthenticationListeners;
use Rudashi\LaravelHistory\Models\History;
use Rudashi\LaravelHistory\Traits\HasHistory;
use Rudashi\LaravelHistory\Traits\HasOperations;

class HistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private FakeUser|Authenticatable $user;

    protected function afterRefreshingDatabase(): void
    {
        Schema::create('__users', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('password');
        });

        Schema::create('__messages', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->softDeletes();
        });

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    public function test_database(): void
    {
        $this->assertDatabaseHas('__users', ['name' => 'Monica']);
        $this->assertDatabaseMissing('__messages', ['title' => '']);
        $this->assertDatabaseMissing('model_histories', ['action' => '']);
    }

    public function test_history_has_relationships(): void
    {
        $message = $this->createModel();
        $history = $message->history->first();

        self::assertInstanceOf(History::class, $history);
        self::assertEquals($message->getKey(), $history->getAttribute('model_id'));
        self::assertEquals(MessageSoftDelete::class, $history->getAttribute('model_type'));

        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));

        self::assertNotNull($history->model);
        self::assertInstanceOf(MessageSoftDelete::class, $history->model);

        self::assertNotNull($history->user);
        self::assertInstanceOf(FakeUser::class, $history->user);
    }

    public function test_model_has_history(): void
    {
        $message = $this->createModel();
        $history = $message->history->first();

        self::assertCount(1, $message->history);
        self::assertInstanceOf(History::class, $history);
        self::assertEquals('created', $history->getAttribute('action'));
        self::assertIsArray($history->getAttribute('meta'));
        self::assertCount(1, $history->getAttribute('meta'));
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $history->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));
    }

    public function test_history_register_create_and_delete_model(): void
    {
        $message = $this->createModel(Message::class);
        $message->delete();
        $message->refresh();

        $history = History::query()->whereMorphedTo('model', $message)->get();
        $created = $history->get(0);
        $deleted = $history->get(1);

        self::assertCount(2, $history);

        self::assertInstanceOf(History::class, $created);
        self::assertInstanceOf(History::class, $deleted);
        self::assertEquals('created', $created->getAttribute('action'));
        self::assertEquals('deleted', $deleted->getAttribute('action'));
        self::assertIsArray($deleted->getAttribute('meta'));
        self::assertCount(0, $deleted->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $deleted->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $deleted->getAttribute('user_type'));
    }

    public function test_history_register_create_and_soft_delete_model(): void
    {
        $message = $this->createAndRemoveModel();
        $history = $message->history->get(1);

        self::assertCount(2, $message->history);

        self::assertInstanceOf(History::class, $history);
        self::assertInstanceOf(Message::class, $message);
        self::assertEquals('created', $message->history->get(0)->getAttribute('action'));
        self::assertEquals('deleted', $history->getAttribute('action'));
        self::assertIsArray($history->getAttribute('meta'));
        self::assertCount(0, $history->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));
    }

    public function test_history_register_model_restore(): void
    {
        $message = $this->createAndRemoveModel();
        $message->restore();
        $message->refresh();

        $history = $message->history->get(2);

        self::assertCount(3, $message->history);
        self::assertInstanceOf(History::class, $history);
        self::assertInstanceOf(Message::class, $message);
        self::assertEquals('created', $message->history->get(0)->getAttribute('action'));
        self::assertEquals('deleted', $message->history->get(1)->getAttribute('action'));
        self::assertEquals('restored', $history->getAttribute('action'));
        self::assertIsArray($history->getAttribute('meta'));
        self::assertCount(0, $history->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));
    }

    public function test_can_disable_history_for_model(): void
    {
        $message = new Message(['title' => 'title at creating']);
        $message->disableHistory();
        $message->save();

        self::assertCount(0, $message->history);
    }

    public function test_history_register_model_update(): void
    {
        $message = $this->createModel();
        $message->update([
            'title' => 'updating title',
        ]);
        $message->refresh();

        $created = $message->history->get(0);
        $history = $message->history->get(1);

        self::assertCount(2, $message->history);
        self::assertInstanceOf(History::class, $history);
        self::assertInstanceOf(History::class, $created);
        self::assertEquals('created', $created->getAttribute('action'));
        self::assertEquals('updated', $history->getAttribute('action'));
        self::assertIsArray($history->getAttribute('meta'));
        self::assertEquals([['key' => 'title', 'old' => 'title at creating', 'new' => 'updating title']], $history->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));
    }

    public function test_history_not_register_excluded_attribute(): void
    {
        $message = $this->createModel(MessageWithoutTitle::class);
        $message->update([
            'title' => 'updating title',
            'description' => 'updating desc',
        ]);
        $message->refresh();

        $created = $message->history->get(0);
        $updated = $message->history->get(1);

        self::assertCount(2, $message->history);

        self::assertInstanceOf(History::class, $created);
        self::assertEquals('created', $created->getAttribute('action'));
        self::assertIsArray($created->getAttribute('meta'));
        self::assertCount(0, $created->getAttribute('meta'));
        self::assertEquals([], $created->getAttribute('meta'));

        self::assertInstanceOf(History::class, $updated);
        self::assertEquals('updated', $updated->getAttribute('action'));
        self::assertIsArray($updated->getAttribute('meta'));
        self::assertCount(1, $updated->getAttribute('meta'));
        self::assertEquals([['key' => 'description', 'old' => null, 'new' => 'updating desc']], $updated->getAttribute('meta'));
    }

    public function test_history_not_register_excluded_events(): void
    {
        $message = $this->createModel(MessageWithoutEvent::class);
        $message->update([
            'description' => 'updating desc',
        ]);
        $message->refresh();

        $history = $message->history->first();

        self::assertCount(1, $message->history);
        self::assertInstanceOf(History::class, $history);
        self::assertEquals('updated', $history->getAttribute('action'));
        self::assertIsArray($history->getAttribute('meta'));
        self::assertCount(1, $history->getAttribute('meta'));
        self::assertEquals([['key' => 'description', 'old' => null, 'new' => 'updating desc']], $history->getAttribute('meta'));
        self::assertEquals($this->user->getKey(), $history->getAttribute('user_id'));
        self::assertEquals(FakeUser::class, $history->getAttribute('user_type'));
    }

    public function test_user_has_registered_history_action(): void
    {
        $message = $this->createModel();

        $operations = $this->user->getAttribute('operations');
        $user_history = $operations->first();

        self::assertEquals(1, $operations->count());
        self::assertInstanceOf(History::class, $user_history);
        self::assertEquals('created', $user_history->getAttribute('action'));
        self::assertEquals([['key' => 'title', 'old' => null, 'new' => 'title at creating']], $user_history->getAttribute('meta'));
        self::assertEquals($message->getKey(), $user_history->getAttribute('model_id'));
        self::assertEquals(MessageSoftDelete::class, $user_history->getAttribute('model_type'));
    }

    public function test_user_has_no_registered_history_actions(): void
    {
        $this->createUser();

        self::assertEquals(0, $this->user->getAttribute('operations')->count());
    }

    public function test_get_model_history(): void
    {
        $message = $this->createModel();
        $message->update(['title' => 'updating title']);
        $history = History::ofModel($message);

        $created = $history->get(0);
        $updated = $history->get(1);

        self::assertCount(2, $history);
        self::assertInstanceOf(History::class, $created);
        self::assertInstanceOf(History::class, $updated);
        self::assertEquals('created', $created->getAttribute('action'));
        self::assertEquals('updated', $updated->getAttribute('action'));
        self::assertEquals($message->getAttribute('id'), $created->getAttribute('model_id'));
        self::assertEquals($message->getAttribute('id'), $updated->getAttribute('model_id'));
    }

    public function test_get_user_history(): void
    {
        $message = $this->createModel();
        $message->update(['title' => 'updating title']);
        $history = History::ofUser($this->user::class, $this->user->getAttribute('id'));

        self::assertCount(2, $history);
        self::assertEquals('created', $history->get(0)->getAttribute('action'));
        self::assertEquals('updated', $history->get(1)->getAttribute('action'));
        self::assertEquals($this->user->getAttribute('id'), $history->get(0)->getAttribute('user_id'));
        self::assertEquals($this->user->getAttribute('id'), $history->get(1)->getAttribute('user_id'));
    }

    public function test_can_register_authentication_event(): void
    {
        Event::fake();

        Auth::login($this->createUser());

        Event::assertListening(Login::class, AuthenticationListeners::class);
        Event::assertDispatched(Login::class);
    }

    public function test_can_register_login_event(): void
    {
        $this->user = $this->createUser();

        Auth::login($this->user);

        $history = History::ofUser($this->user);
        $login = $history->first();

        self::assertCount(1, $history);
        self::assertInstanceOf(History::class, $login);
        self::assertEquals('Login', $login->getAttribute('action'));
        self::assertIsArray($login->getAttribute('meta'));
        self::assertCount(0, $login->getAttribute('meta'));
        self::assertNull($login->getAttribute('model_id'));
        self::assertNull($login->getAttribute('model_type'));
    }

    public function test_can_register_logout_event(): void
    {
        $this->user = $this->createUser();
        $this->actingAs($this->user);
        Auth::logout();

        $history = History::ofUser($this->user);
        $login = $history->first();

        self::assertCount(1, $history);
        self::assertInstanceOf(History::class, $login);
        self::assertEquals('Logout', $login->getAttribute('action'));
        self::assertIsArray($login->getAttribute('meta'));
        self::assertCount(0, $login->getAttribute('meta'));
        self::assertNull($login->getAttribute('model_id'));
        self::assertNull($login->getAttribute('model_type'));
    }

    private function createAndRemoveModel(string $class = MessageSoftDelete::class): MessageSoftDelete
    {
        $message = $this->createModel($class);
        self::assertCount(1, $message->history);
        self::assertInstanceOf(MessageSoftDelete::class, $message);

        $message->delete();
        $message->refresh();

        return $message;
    }

    private function createModel(string $class = MessageSoftDelete::class): Message
    {
        return (new $class())->query()->create([
            'title' => 'title at creating',
        ]);
    }

    private function createUser(): FakeUser|Model|Authenticatable
    {
        return FakeUser::query()->create(['name' => 'Monica', 'password' => 'secret']);
    }
}

class FakeUser extends User
{
    use HasOperations;

    public $timestamps = false;
    protected $guarded = [];
    protected $table = '__users';
}

class Message extends Model implements HasHistoryInterface
{
    use HasHistory;

    public $timestamps = false;
    protected $table = '__messages';
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
        return ['title'];
    }
}

class MessageWithoutEvent extends Message
{
    public function excludedHistoryModelEvents(): array
    {
        return ['created'];
    }
}
