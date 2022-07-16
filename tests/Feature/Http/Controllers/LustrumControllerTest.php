<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Activity;
use App\Models\Page;
use App\Models\Role;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LustrumControllerTest extends TestCase
{
    use WithFaker;

    private ?string $host = null;

    /**
     * @before
     */
    public function setLustrumHost(): void
    {
        $this->afterApplicationCreated(function () {
            $this->host = Config::get('gumbo.lustrum-domains', [])[0] ?? null;

            if ($this->host === null) {
                $this->markTestSkipped('No Lustrum domains specified');
            }

            $this->host = "http://{$this->host}";
        });
    }

    public function test_get_index(): void
    {
        $this->get($this->host)
            ->assertOk()
            ->assertSee('Er is er een jarig');
    }

    public function test_with_proper_activities(): void
    {
        $lustrumRole = Role::findOrCreate('lucie');
        assert($lustrumRole instanceof Role);

        $activities = Activity::factory()->create([
            'role_id' => $lustrumRole->getKey(),
        ]);

        $this->get($this->host)
            ->assertOk()
            ->assertSee($activities->name);
    }

    public function test_with_custom_page(): void
    {
        // Delete existing page
        optional(Page::findBySlug('lustrum'))->delete();

        // Make a new one
        $lustrumPage = Page::factory()
            ->withContents()
            ->withSummary()
            ->create(['slug' => 'lustrum']);

        $this->get($this->host)
            ->assertOk()
            ->assertSee($lustrumPage->title)
            ->assertSee($lustrumPage->summary)
            ->assertSee($lustrumPage->html->toHtml(), false);
    }

    public function test_with_merchandise(): void
    {
        /** @var Category $shopCategory */
        $shopCategory = Category::factory()
            ->has(Product::factory(4)->hasVariants()->visible())
            ->visible()
            ->create(['slug' => 'lustrum']);

        /** @var Collection<Product> $shopProducts */
        $shopProducts = $shopCategory->products;

        $result = $this->get($this->host)
            ->assertOk();

        foreach ($shopProducts as $product) {
            $result->assertSee($product->name);
            $result->assertSee(route('shop.product', $product));
        }
    }

    public function test_get_main_site_routes(): void
    {
        $this->get($this->host . route('activity.index', [], false))
            ->assertNotFound();

        $this->get($this->host . route('account.index', [], false))
            ->assertNotFound();

        $this->get($this->host . route('login', [], false))
            ->assertNotFound();
    }
}
