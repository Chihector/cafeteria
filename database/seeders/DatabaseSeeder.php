<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Pedido;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Carlos Muñoz',
            'email' => 'barista@test.com',
            'password' => bcrypt('password'),
        ]);

        // Crear Mesas
        for ($i = 1; $i <= 10; $i++) {
            Mesa::create([
                'numero' => $i,
                'capacidad' => 4,
                'estado' => 'disponible',
            ]);
        }

        // Crear Productos desde Figma
        $productosData = [
            [
                'nombre' => 'Cappuccino Clásico', 'precio' => 2900, 'categoria' => 'coffee',
                'descripcion' => 'Espresso doble con leche vaporizada y una capa de espuma de leche perfecta.',
                'imagen' => 'https://images.unsplash.com/photo-1534687941688-651ccaafbff8?w=400&h=300&fit=crop&auto=format',
                'destacado' => true, 'alergenos' => ['Lácteos'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Latte Macchiato Caramel', 'precio' => 3500, 'categoria' => 'coffee',
                'descripcion' => 'Leche vaporizada, vainilla, espresso intenso y un toque generoso de caramelo.',
                'imagen' => 'https://images.unsplash.com/photo-1557006021-b85faa2bc5e2?w=400&h=300&fit=crop&auto=format',
                'destacado' => true, 'alergenos' => ['Lácteos'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Croissant de Almendras', 'precio' => 2500, 'categoria' => 'pastries',
                'descripcion' => 'Croissant francés relleno de crema de almendras y cubierto de almendras laminadas.',
                'imagen' => 'https://images.unsplash.com/photo-1558401391-7899b4bd5bbf?w=400&h=300&fit=crop&auto=format',
                'destacado' => true, 'alergenos' => ['Gluten', 'Lácteos', 'Huevo', 'Frutos Secos'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Muffin de Arándanos', 'precio' => 2200, 'categoria' => 'pastries',
                'descripcion' => 'Esponjoso muffin horneado con arándanos frescos y topping de azúcar crujiente.',
                'imagen' => 'https://images.unsplash.com/photo-1598514982205-f36b96d1e8d4?w=400&h=300&fit=crop&auto=format',
                'destacado' => false, 'alergenos' => ['Gluten', 'Lácteos', 'Huevo'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Sándwich Caprese', 'precio' => 4500, 'categoria' => 'sandwiches',
                'descripcion' => 'Pan ciabatta rústico, mozzarella fresca, tomates, albahaca y pesto genovés.',
                'imagen' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=400&h=300&fit=crop&auto=format',
                'destacado' => false, 'alergenos' => ['Gluten', 'Lácteos'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Avocado Toast', 'precio' => 4900, 'categoria' => 'sandwiches',
                'descripcion' => 'Tostada de pan de masa madre con palta hass molida, tomate cherry y sésamo tostado.',
                'imagen' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5e?w=400&h=300&fit=crop&auto=format',
                'destacado' => false, 'alergenos' => ['Gluten'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Iced Matcha Latte', 'precio' => 4200, 'categoria' => 'cold_drinks',
                'descripcion' => 'Té matcha japonés de grado ceremonial con leche fría y hielo.',
                'imagen' => 'https://images.unsplash.com/photo-1536285514682-1d5db0739970?w=400&h=300&fit=crop&auto=format',
                'destacado' => false, 'alergenos' => ['Lácteos'], 'vegetariano' => true,
            ],
            [
                'nombre' => 'Frappé de Moca', 'precio' => 4800, 'categoria' => 'cold_drinks',
                'descripcion' => 'Café, leche y chocolate mezclados con hielo, coronados con crema batida.',
                'imagen' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&h=300&fit=crop&auto=format',
                'destacado' => false, 'alergenos' => ['Lácteos'], 'vegetariano' => true,
            ]
        ];

        $prods = [];
        foreach ($productosData as $p) {
            $prods[$p['nombre']] = Producto::create($p);
        }

        $kdsOrders = [
            [
                'mesa' => 4, 'status' => 'pendiente', 
                'items' => [
                    ['name' => 'Cappuccino Clásico', 'qty' => 2, 'notes' => 'Leche descremada'],
                    ['name' => 'Croissant de Almendras', 'qty' => 2, 'notes' => null],
                    ['name' => 'Iced Matcha Latte', 'qty' => 1, 'notes' => 'Sin azúcar'],
                ]
            ],
            [
                'mesa' => 7, 'status' => 'pendiente', 
                'items' => [
                    ['name' => 'Latte Macchiato Caramel', 'qty' => 1, 'notes' => null],
                    ['name' => 'Muffin de Arándanos', 'qty' => 1, 'notes' => null],
                ]
            ],
            [
                'mesa' => 2, 'status' => 'preparando', 
                'items' => [
                    ['name' => 'Sándwich Caprese', 'qty' => 1, 'notes' => 'Sin pesto'],
                    ['name' => 'Avocado Toast', 'qty' => 1, 'notes' => null],
                    ['name' => 'Frappé de Moca', 'qty' => 1, 'notes' => 'Con crema'],
                ]
            ]
        ];

        foreach ($kdsOrders as $orderData) {
            $total = 0;
            foreach ($orderData['items'] as $item) {
                $total += $prods[$item['name']]->precio * $item['qty'];
            }

            $pedido = Pedido::create([
                'mesa_id' => $orderData['mesa'],
                'user_id' => $user->id,
                'estado' => $orderData['status'],
                'total' => $total,
            ]);

            Mesa::where('numero', $orderData['mesa'])->update(['estado' => 'ocupada']);

            foreach ($orderData['items'] as $item) {
                $pedido->detalles()->create([
                    'producto_id' => $prods[$item['name']]->id,
                    'cantidad' => $item['qty'],
                    'precio_unitario' => $prods[$item['name']]->precio,
                    'notas_especiales' => $item['notes'],
                ]);
            }
        }
    }
}
