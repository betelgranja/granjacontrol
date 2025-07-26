<?php
class DatabaseConnection {
    private $connection;
    
    public function __construct() {
        $host = 'aws-0-eu-west-1.pooler.supabase.com';
        $port = '6543';
        $dbname = 'postgres';
        $user = 'postgres.serqaiqpiknjyghdpfdv';
        $password = '7369281Dairo';
        
        $connection_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
        
        $this->connection = pg_connect($connection_string);
        
        if (!$this->connection) {
            throw new Exception("No se pudo conectar a la base de datos PostgreSQL");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function validateUser($username, $password) {
        $sql = "SELECT id, usuario, contrasena FROM login WHERE usuario = $1";
        $result = pg_query_params($this->connection, $sql, [$username]);
        
        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Comparación directa (en producción usa password_hash)
            if ($password === $user['contrasena']) {
                return $user;
            }
        }
        
        return false;
    }
}
?>