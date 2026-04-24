<?php
// ── SISTEMA DE NIVELES ──────────────────────────────────────────
 
$NIVELES = [
    1 => ['nombre' => 'Espectador',       'xp_min' => 0,    'xp_max' => 299,  'icono' => '🎟️'],
    2 => ['nombre' => 'Aficionado',       'xp_min' => 300,  'xp_max' => 699,  'icono' => '🎬'],
    3 => ['nombre' => 'Cinéfilo',         'xp_min' => 700,  'xp_max' => 1499, 'icono' => '🎭'],
    4 => ['nombre' => 'Crítico',          'xp_min' => 1500, 'xp_max' => 2999, 'icono' => '🏅'],
    5 => ['nombre' => 'Maestro Cinéfilo', 'xp_min' => 3000, 'xp_max' => 9999, 'icono' => '🏆'],
];
 
function calcular_nivel($xp) {
    global $NIVELES;
    $nivel = 1;
    foreach ($NIVELES as $n => $datos) {
        if ($xp >= $datos['xp_min']) $nivel = $n;
    }
    return $nivel;
}
 
function get_nivel_datos($nivel) {
    global $NIVELES;
    return $NIVELES[$nivel] ?? $NIVELES[1];
}
 
function xp_para_siguiente_nivel($xp) {
    global $NIVELES;
    $nivel_actual = calcular_nivel($xp);
    if ($nivel_actual >= 5) return 0;
    return $NIVELES[$nivel_actual + 1]['xp_min'] - $xp;
}
 
function progreso_nivel($xp) {
    global $NIVELES;
    $nivel_actual = calcular_nivel($xp);
    if ($nivel_actual >= 5) return 100;
    $xp_inicio = $NIVELES[$nivel_actual]['xp_min'];
    $xp_fin = $NIVELES[$nivel_actual + 1]['xp_min'];
    return min(100, round(($xp - $xp_inicio) / ($xp_fin - $xp_inicio) * 100));
}
 
function actualizar_nivel_usuario($pdo, $usuario_id, $xp_nuevo) {
    $nivel_nuevo = calcular_nivel($xp_nuevo);
    $stmt = $pdo->prepare('SELECT nivel FROM usuarios WHERE id = ?');
    $stmt->execute([$usuario_id]);
    $nivel_anterior = $stmt->fetch()['nivel'] ?? 1;
 
    if ($nivel_nuevo > $nivel_anterior) {
        $stmt = $pdo->prepare('UPDATE usuarios SET nivel = ? WHERE id = ?');
        $stmt->execute([$nivel_nuevo, $usuario_id]);
        return $nivel_nuevo; // devuelve el nuevo nivel para la notificación
    }
    return false;
}
 
function get_color_categoria($categoria) {
    return match($categoria) {
        'bronce'    => '#cd7f32',
        'plata'     => '#c0c0c0',
        'oro'       => '#f5c518',
        'legendario'=> '#9b59b6',
        'platino'   => '#4fc3f7',
        default     => '#888',
    };
}
 
function get_icono_categoria($categoria) {
    return match($categoria) {
        'bronce'    => '🥉',
        'plata'     => '🥈',
        'oro'       => '🥇',
        'legendario'=> '💎',
        'platino'   => '🏆',
        default     => '⭐',
    };
}