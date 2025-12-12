<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Link;
use App\Models\ClickLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\ClicksChartWidget;
use App\Filament\Widgets\PopularLinksWidget;
use App\Filament\Widgets\RecentActivityWidget;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = Admin::factory()->create([
            'username' => 'testadmin',
            'password_hash' => bcrypt('password'),
            'is_active' => true,
        ]);
        
        $this->actingAs($this->admin, 'filament');
    }

    /** @test */
    public function stats_overview_widget_displays_correct_data()
    {
        $links = Link::factory()->count(10)->create();
        $disabledLinks = Link::factory()->count(3)->create(['disabled' => true]);
        
        // Add some clicks
        $links->first()->update(['clicks' => 100]);
        ClickLog::factory()->count(50)->create(['short_code' => $links->first()->short_code]);
        ClickLog::factory()->count(25)->create(['timestamp' => now()]);
        
        $widget = new StatsOverviewWidget();
        
        $stats = $widget->getStats();
        
        $this->assertEquals('13', $stats[0]->getValue()); // Total links
        $this->assertEquals('150', $stats[1]->getValue()); // Total clicks
        $this->assertEquals('25', $stats[2]->getValue()); // Today clicks
        $this->assertEquals('1', $stats[3]->getValue()); // Total admins
    }

    /** @test */
    public function clicks_chart_widget_displays_correct_data()
    {
        // Create clicks for the last 7 days
        for ($i = 6; $i >= 0; $i--) {
            ClickLog::factory()->count($i + 1)->create([
                'timestamp' => now()->subDays($i)
            ]);
        }
        
        $widget = new ClicksChartWidget();
        
        $data = $widget->getData();
        
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(7, $data['labels']);
        $this->assertCount(1, $data['datasets']);
        $this->assertEquals('Jumlah Klik', $data['datasets'][0]['label']);
    }

    /** @test */
    public function popular_links_widget_displays_correct_data()
    {
        $popularLink = Link::factory()->create(['clicks' => 100]);
        $normalLink = Link::factory()->create(['clicks' => 10]);
        $lessPopularLink = Link::factory()->create(['clicks' => 50]);
        
        $widget = new PopularLinksWidget();
        
        $table = $widget->table(\Filament\Tables\Table::make());
        $query = $table->getQuery();
        
        $links = $query->get();
        
        $this->assertCount(3, $links);
        $this->assertEquals($popularLink->id, $links[0]->id);
        $this->assertEquals($lessPopularLink->id, $links[1]->id);
        $this->assertEquals($normalLink->id, $links[2]->id);
    }

    /** @test */
    public function recent_activity_widget_displays_correct_data()
    {
        $link = Link::factory()->create(['short_code' => 'test-link']);
        
        $oldClick = ClickLog::factory()->create([
            'short_code' => $link->short_code,
            'timestamp' => now()->subHours(2),
            'ip_address' => '192.168.1.1',
        ]);
        
        $recentClick = ClickLog::factory()->create([
            'short_code' => $link->short_code,
            'timestamp' => now()->subMinutes(30),
            'ip_address' => '192.168.1.2',
        ]);
        
        $widget = new RecentActivityWidget();
        
        $table = $widget->table(\Filament\Tables\Table::make());
        $query = $table->getQuery();
        
        $activities = $query->get();
        
        $this->assertCount(2, $activities);
        $this->assertEquals($recentClick->id, $activities[0]->id);
        $this->assertEquals($oldClick->id, $activities[1]->id);
    }

    /** @test */
    public function widgets_handle_empty_data()
    {
        $widget = new StatsOverviewWidget();
        $stats = $widget->getStats();
        
        $this->assertEquals('0', $stats[0]->getValue()); // Total links
        $this->assertEquals('0', $stats[1]->getValue()); // Total clicks
        $this->assertEquals('0', $stats[2]->getValue()); // Today clicks
        $this->assertEquals('1', $stats[3]->getValue()); // Total admins (created in setUp)
    }

    /** @test */
    public function chart_widget_handles_empty_data()
    {
        $widget = new ClicksChartWidget();
        
        $data = $widget->getData();
        
        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(7, $data['labels']);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0], $data['datasets'][0]['data']);
    }

    /** @test */
    public function popular_links_widget_handles_empty_data()
    {
        $widget = new PopularLinksWidget();
        
        $table = $widget->table(\Filament\Tables\Table::make());
        $query = $table->getQuery();
        
        $links = $query->get();
        
        $this->assertCount(0, $links);
    }

    /** @test */
    public function recent_activity_widget_handles_empty_data()
    {
        $widget = new RecentActivityWidget();
        
        $table = $widget->table(\Filament\Tables\Table::make());
        $query = $table->getQuery();
        
        $activities = $query->get();
        
        $this->assertCount(0, $activities);
    }

    /** @test */
    public function widgets_refresh_data()
    {
        $link = Link::factory()->create(['clicks' => 10]);
        
        // Initial state
        $widget = new StatsOverviewWidget();
        $stats = $widget->getStats();
        $this->assertEquals('1', $stats[0]->getValue());
        
        // Add more clicks
        $link->update(['clicks' => 20]);
        
        // Widget should reflect new data
        $widget = new StatsOverviewWidget();
        $stats = $widget->getStats();
        $this->assertEquals('1', $stats[0]->getValue()); // Still 1 link
        $this->assertEquals('20', $stats[1]->getValue()); // Updated clicks
    }
}