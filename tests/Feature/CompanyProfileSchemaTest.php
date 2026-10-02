<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CompanyProfileSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_installation_only_contains_company_profile_structure(): void
    {
        foreach (['companies', 'products', 'pillars', 'process_steps', 'inquiries', 'users'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        foreach (['transactions', 'transaction_details', 'categories', 'catalog_products'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }

        foreach (['harga_beli', 'harga_jual', 'stok', 'category_id', 'kode_produk'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
        }

        foreach (['role', 'no_hp', 'alamat'] as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column));
        }

        $this->assertTrue(Schema::hasColumn('users', 'is_admin'));
        $company = Company::factory()->create();
        $product = Product::factory()->for($company)->create(['category' => 'Packaging']);

        $this->assertTrue($company->products->sole()->is($product));
        $this->assertTrue($product->company->is($company));
        $this->assertSame('Packaging', $product->category);
    }
}
