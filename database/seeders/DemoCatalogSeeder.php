<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $out = $this->command?->getOutput();

        DB::transaction(function () use ($out) {
            // ── 0) Branch destino (campo correcto: branch_name)
            $branchName = 'Casa Matriz EcoPC';
            /** @var Branch $branch */
            $branch = Branch::where('branch_name', $branchName)->firstOrFail();

            // ── 1) Brands (Lenovo, Dell) en la branch
            [$bNew, $bUpd] = [0, 0];
            $brandSlugCol = Schema::hasColumn('brands', 'slug');

            $lenovo = $this->upsertBrandInBranch($branch->id, 'Lenovo', $brandSlugCol, $bNew, $bUpd);
            $this->upsertBrandInBranch($branch->id, 'Dell',   $brandSlugCol, $bNew, $bUpd);

            // ── 2) Categories: Notebook (root) y Notebook mini (hija)
            $catSlugCol = Schema::hasColumn('categories', 'slug');

            $notebook = Category::firstOrCreate(
                $catSlugCol ? ['slug' => 'notebook'] : ['name' => 'Notebook', 'parent_id' => null],
                ['name' => 'Notebook', 'parent_id' => null] + ($catSlugCol ? ['slug' => 'notebook'] : [])
            );

            $notebookMini = Category::firstOrCreate(
                $catSlugCol ? ['slug' => 'notebook-mini'] : ['name' => 'Notebook mini', 'parent_id' => $notebook->id],
                ['name' => 'Notebook mini', 'parent_id' => $notebook->id] + ($catSlugCol ? ['slug' => 'notebook-mini'] : [])
            );

            // ── 3) Producto X390-B (Lenovo) con atributos y categoría Notebook
            $data = [
                'branch_id'       => $branch->id,
                'sku'             => 'X390-B',
                'name'            => 'Lenovo thinkpad x390 Grado B',
                'brand_id'        => $lenovo->id,
                'price'           => 329990,
                'is_active'       => true,
                'attributes_json' => ['grade' => 'B', 'RAM' => '16 GB', 'CPU' => 'I7 8350U'],
            ];

            $product = Product::where('branch_id', $branch->id)
                ->whereRaw('LOWER(sku)=LOWER(?)', ['X390-B'])
                ->first();

            $created = false;
            if ($product) {
                $product->update($data);
            } else {
                $product = Product::create($data);
                $created = true;
            }

            // pivot categorías (solo Notebook)
            $product->categories()->sync([
                $notebook->id => ['assigned_at' => now()],
            ]);

            // ── 4) Output estilo “checklist”
            if ($out) {
                $out->writeln('');
                $out->writeln('✅ Seeder de catálogo completado:');
                $out->writeln('  - Branch destino: <info>'.$branchName.'</info> (id '.$branch->id.')');
                $out->writeln('  - Marcas creadas/actualizadas: <info>'.$bNew.'</info>/<info>'.$bUpd.'</info> (Lenovo, Dell)');
                $out->writeln('  - Categorías: <info>Notebook</info> (root), <info>Notebook mini</info> (hija de Notebook)');
                $out->writeln('  - Producto '.($created ? 'creado' : 'actualizado').': <info>X390-B</info> (Lenovo) con categoría <info>Notebook</info>');
                $out->writeln('');
            }
        });
    }

    /**
     * Crea/actualiza una Brand por (branch_id + name CI).
     * Incrementa contadores por referencia.
     */
    private function upsertBrandInBranch(int $branchId, string $name, bool $hasSlug, int &$created, int &$updated): Brand
    {
        $existing = Brand::where('branch_id', $branchId)
            ->whereRaw('LOWER(name)=LOWER(?)', [$name])
            ->first();

        $payload = ['name' => $name] + ($hasSlug ? ['slug' => Str::slug($name)] : []);

        if ($existing) {
            $existing->update($payload);
            $updated++;
            return $existing;
        }

        $brand = Brand::create(['branch_id' => $branchId] + $payload);
        $created++;
        return $brand;
    }
}
