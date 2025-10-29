<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_all_blogs()
    {
        Blog::factory()->count(3)->create();

        $response = $this->getJson('/api/blogs');

        $response->assertStatus(200)
                 ->assertJson(['isSuccess' => true])
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_filters_blogs_by_category()
    {
        Blog::factory()->create(['category' => 'Tech']);
        Blog::factory()->create(['category' => 'Food']);

        $response = $this->getJson('/api/blogs?category=Tech');

        $response->assertStatus(200)
                 ->assertJsonFragment(['category' => 'Tech'])
                 ->assertJsonMissing(['category' => 'Food']);
    }

    /** @test */
    public function it_creates_a_new_blog()
    {
        $data = [
            'title' => 'New Blog',
            'author' => 'john@example.com',
            'description' => 'Sample desc',
            'category' => 'Tech'
        ];

        $response = $this->postJson('/api/blogs', $data);

        $response->assertStatus(200)
                 ->assertJson(['isSuccess' => true])
                 ->assertJsonFragment(['title' => 'New Blog']);

        $this->assertDatabaseHas('blogs', ['title' => 'New Blog']);
    }

    /** @test */
    public function it_fails_validation_when_required_fields_missing()
    {
        $response = $this->postJson('/api/blogs', []);

        $response->assertStatus(422)
                 ->assertJson(['isSuccess' => false])
                 ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function it_shows_a_specific_blog()
    {
        $blog = Blog::factory()->create();

        $response = $this->getJson('/api/blogs/' . $blog->id);

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $blog->id]);
    }

    /** @test */
    public function it_updates_a_blog_when_authorized()
    {
        $user = User::factory()->create(['email' => 'author@example.com']);
        $this->actingAs($user);

        $blog = Blog::factory()->create(['author' => $user->email]);

        $data = ['title' => 'Updated Title'];

        $response = $this->putJson("/api/blogs/{$blog->id}", $data);

        $response->assertStatus(200)
                 ->assertJson(['isSuccess' => true]);

        $this->assertDatabaseHas('blogs', ['title' => 'Updated Title']);
    }

    /** @test */
    public function it_denies_update_if_user_not_author()
    {
        $user = User::factory()->create(['email' => 'notme@example.com']);
        $this->actingAs($user);

        $blog = Blog::factory()->create(['author' => 'author@example.com']);

        $response = $this->putJson("/api/blogs/{$blog->id}", ['title' => 'Hack']);

        $response->assertStatus(401)
                 ->assertJson(['isSuccess' => false]);
    }

    /** @test */
    public function it_deletes_a_blog_when_authorized()
    {
        $user = User::factory()->create(['email' => 'author@example.com']);
        $this->actingAs($user);

        $blog = Blog::factory()->create(['author' => $user->email]);

        $response = $this->deleteJson("/api/blogs/{$blog->id}");

        $response->assertStatus(200)
                 ->assertJson(['isSuccess' => true]);

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    }
}
