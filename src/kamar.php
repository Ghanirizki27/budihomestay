<?php
$angka_rahasia = rand(1, 10);

if (isset($_POST['tebak'])) {
    $tebakan = $_POST['tebakan'];
    if ($tebakan == $angka_rahasia) {
        echo "Selang!";
    } elseif ($tebakan < $angka_rahasia) {
        echo "Terlalu rendah!";
    } else {
        echo "Terlalu tinggi!";
    }
}
?>

<form method="post">
    <input type="number" name="tebakan">
    <button type="submit" name="tebak">Tebak!</button>
</form>


