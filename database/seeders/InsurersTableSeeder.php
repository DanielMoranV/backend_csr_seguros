<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insurers = [
            ["id" => 1, "name" => "PETROLEOS DEL PERU PETROPERU S.A.", "shipping_period" => 45],
            ["id" => 2, "name" => "SISTEMAS ALTERNATIVOS DE BENEFICIOS S.A.", "shipping_period" => 45],
            ["id" => 3, "name" => "MAPFRE PERU COMPAÑIA DE SEGUROS Y REASEGUROS S.A.", "shipping_period" => 90],
            ["id" => 4, "name" => "LA POSITIVA SEGUROS Y REASEGUROS S.A.A.", "shipping_period" => 45],
            ["id" => 5, "name" => "RIMAC S.A. ENTIDAD PRESTADORA DE SALUD", "shipping_period" => 90],
            ["id" => 6, "name" => "PACIFICO S.A. ENT. PRESTADORA DE SALUD", "shipping_period" => 45],
            ["id" => 7, "name" => "LA POSITIVA S.A. ENTIDAD PRESTADORA DE SALUD", "shipping_period" => 45],
            ["id" => 8, "name" => "ONCOSALUD S.A.C.", "shipping_period" => 45],
            ["id" => 9, "name" => "RIMAC SEGUROS Y REASEGUROS S.A.", "shipping_period" => 90],
            ["id" => 10, "name" => "SANITAS PERU S.A. - EPS", "shipping_period" => 45],
            ["id" => 11, "name" => "PACIFICO COMPAÑIA DE SEGUROS Y REASEGUROS S.A.", "shipping_period" => 45],
            ["id" => 12, "name" => "FONDO DE EMPLEADOS DEL BANCO DE LA NACION", "shipping_period" => 45],
            ["id" => 13, "name" => "MAPFRE PERU S.A. ENTIDAD PRESTADORA DE SALUD", "shipping_period" => 90],
            ["id" => 14, "name" => "IMPULSA365 S.A.C.", "shipping_period" => 45],
            ["id" => 15, "name" => "CHUBB PERU S.A. COMPAÑIA DE SEGUROS Y REASEGUROS", "shipping_period" => 45],
            ["id" => 16, "name" => "AFOCAT - PIURA", "shipping_period" => 45],
            ["id" => 17, "name" => "AFOCAT-TRANS - REGION PIURA", "shipping_period" => 45],
            ["id" => 18, "name" => "RIMAC SEGUROS Y REASEGUROS - ST", "shipping_period" => 90],
            ["id" => 19, "name" => "HEALTH CARE ADMINISTRATION RED SALUD S.A.C.", "shipping_period" => 45],
            ["id" => 20, "name" => "PROTECTA SECURITY ACCIDENTES PERSONALES", "shipping_period" => 45],
        ];

        DB::table('insurers')->insert($insurers);
    }
}
