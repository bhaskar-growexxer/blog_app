<?php

namespace Tests\Unit;

use App\Http\Controllers\BlogController;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class BlogControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_all_blogs_when_no_filters_applied()
    {
        $mockedBlogs = collect([
            (object)['id' => 1, 'title' => 'Blog 1', 'created_at' => now()],
            (object)['id' => 2, 'title' => 'Blog 2', 'created_at' => now()],
        ]);

        $blogMock = Mockery::mock('alias:' . Blog::class);
        $blogMock->shouldReceive('all')->once()->andReturn($mockedBlogs);

        $controller = new BlogController();
        $request = new Request();

        $response = $controller->index($request);
        $data = $response->getData(true);

        $this->assertTrue($data['isSuccess']);
        $this->assertCount(2, $data['data']);
    }

    /** @test */
    public function it_filters_blogs_by_category()
    {
        $mockedBlogs = collect([
            (object)['id' => 1, 'title' => 'Tech Blog', 'category' => 'Tech', 'created_at' => now()],
        ]);

        $blogMock = Mockery::mock('alias:' . Blog::class);
        $blogMock->shouldReceive('where')
                 ->with('category', 'Tech')
                 ->andReturnSelf();
        $blogMock->shouldReceive('get')->once()->andReturn($mockedBlogs);

        $controller = new BlogController();
        $request = new Request(['category' => 'Tech']);

        $response = $controller->index($request);
        $data = $response->getData(true);

        $this->assertTrue($data['isSuccess']);
        $this->assertEquals('Tech', $data['data'][0]['category']);
    }

    /** @test */
    public function it_creates_a_blog_successfully()
    {
        $mockedBlog = Mockery::mock(Blog::class)->makePartial();
        $mockedBlog->id = 1;
        $mockedBlog->title = 'My Blog';
        $mockedBlog->author = 'john@example.com';
        $mockedBlog->description = 'Demo';
        $mockedBlog->category = 'Tech';
        $mockedBlog->created_at = now();

        $blogMock = Mockery::mock('alias:' . Blog::class);
        $blogMock->shouldReceive('create')->once()->andReturn($mockedBlog);

        $controller = new BlogController();

        $request = new Request([
            'title' => 'My Blog',
            'author' => 'john@example.com',
            'description' => 'Demo',
            'category' => 'Tech',
        ]);

        $response = $controller->store($request);
        $data = $response->getData(true);

        $this->assertTrue($data['isSuccess']);
        $this->assertEquals('My Blog', $data['data']['title']);
    }

    /** @test */
    public function it_returns_error_when_id_is_missing_in_show()
    {
        $controller = new BlogController();
        $response = $controller->show('');
        $data = $response->getData(true);

        $this->assertFalse($data['isSuccess']);
        $this->assertEquals(BlogController::ID_REQUIRED_MESSAGE, $data['mesage']);
    }

    /** @test */
    public function it_returns_blog_details_when_id_is_provided()
    {
        $mockedBlog = Mockery::mock(Blog::class);
        $mockedBlog->id = 1;
        $mockedBlog->title = 'Sample Blog';

        $blogMock = Mockery::mock('alias:' . Blog::class);
        $blogMock->shouldReceive('find')->with(1)->andReturn($mockedBlog);

        $controller = new BlogController();
        $response = $controller->show(1);
        $data = $response->getData(true);

        $this->assertTrue($data['isSuccess']);
        $this->assertEquals(1, $data['data']['id']);
    }
}
