<?php
// config/supabase.php
// Configuración de Supabase para PHP

// Reemplaza estos valores con los de tu proyecto Supabase
const SUPABASE_URL = 'https://fcevakmwpcujvaermkzo.supabase.co';
const SUPABASE_API_KEY = 'sb_publishable_WQGmuNXfqjdk7o9heE1hfA_N_-uJ6IS';

function supabase_request($endpoint, $method = 'GET', $data = null, $headers = []) {
    $url = SUPABASE_URL . $endpoint;
    $ch = curl_init($url);
    $defaultHeaders = [
        'apikey: ' . SUPABASE_API_KEY,
        'Authorization: Bearer ' . SUPABASE_API_KEY,
        'Content-Type: application/json',
    ];
    $allHeaders = array_merge($defaultHeaders, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error, 'status' => $httpcode];
    }
    curl_close($ch);
    return ['data' => json_decode($response, true), 'status' => $httpcode];
}
