<?php
$app = require __DIR__.'/bootstrap.php';
$settings = app(\Filamizer\Settings\StudioSettings::class);
\Filamizer\Models\StudioProfile::firstOrCreate(['name'=>'Indigo Studio','user_id'=>null], ['data'=>$settings->toArray()]);
echo "Fictional shared profile seeded for filming.\n";
