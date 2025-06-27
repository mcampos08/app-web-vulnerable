<?php
// Configuración de base de datos (A02: Credenciales expuestas)
$db_host = '192.168.100.60';
$db_user = 'webapp_user';
$db_pass = 'webapp123'; // A02: Contraseña en texto plano
$db_name = 'clinica_db';

// Manejo de errores para desarrollo (A02: Information disclosure)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Procesar requests AJAX antes de cualquier output HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Conectar a MySQL (A02: Sin cifrado de conexión)
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($mysqli->connect_error) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error de conexión: ' . $mysqli->connect_error]);
        exit;
    }

    // Función para log (vulnerable)
    function logActivity($mysqli, $action, $details = '') {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // A03: SQL Injection vulnerable
        $query = "INSERT INTO system_logs (user_id, action, ip_address, details) VALUES ($user_id, '$action', '$ip', '$details')";
        $mysqli->query($query);
    }

    // Manejar login (A07: Autenticación vulnerable)
    if ($_POST['action'] == 'login') {
        header('Content-Type: application/json');
        
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // A03: SQL Injection vulnerable
        $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
        
        $result = $mysqli->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            logActivity($mysqli, 'login', "Successful login for user: $username");
            
            echo json_encode([
                'success' => true, 
                'message' => "Bienvenido {$user['username']} ({$user['role']})",
                'user' => [
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            logActivity($mysqli, 'failed_login', "Failed login attempt for user: $username");
            echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        }
        exit;
    }

    // Buscar pacientes (A01: Sin control de acceso)
    if ($_POST['action'] == 'search') {
        header('Content-Type: application/json');
        
        $patient_id = $_POST['patient_id'] ?? '';
        $patient_name = $_POST['patient_name'] ?? '';
        $doctor_filter = $_POST['doctor_filter'] ?? '';
        
        // A03: SQL Injection vulnerable
        $query = "SELECT * FROM patients WHERE 1=1";
        
        if ($patient_id) {
            $query .= " AND id='$patient_id'"; // Vulnerable
        }
        if ($patient_name) {
            $query .= " AND name LIKE '%$patient_name%'"; // Vulnerable
        }
        if ($doctor_filter) {
            $query .= " AND doctor='$doctor_filter'"; // Vulnerable
        }
        
        $result = $mysqli->query($query);
        $patients = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
        }
        
        logActivity($mysqli, 'search', "Patient search performed");
        echo json_encode($patients);
        exit;
    }

    // Mostrar todos los pacientes (A01: Sin autorización)
    if ($_POST['action'] == 'show_all') {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM patients";
        $result = $mysqli->query($query);
        $patients = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
        }
        
        logActivity($mysqli, 'view_all', "All patients viewed");
        echo json_encode($patients);
        exit;
    }

    // Exportar datos (A01: Sin control de acceso)
    if ($_POST['action'] == 'export') {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM patients";
        $result = $mysqli->query($query);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        logActivity($mysqli, 'export', "Data exported");
        echo json_encode($data);
        exit;
    }

    // Ver logs (A01: Sin verificación de permisos)
    if ($_POST['action'] == 'logs') {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM system_logs ORDER BY timestamp DESC LIMIT 50";
        $result = $mysqli->query($query);
        $logs = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        echo json_encode($logs);
        exit;
    }

    // Gestionar usuarios (A01: Sin autorización)
    if ($_POST['action'] == 'users') {
        header('Content-Type: application/json');
        
        $query = "SELECT id, username, password, role, email FROM users"; // A02: Passwords expuestos
        $result = $mysqli->query($query);
        $users = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        echo json_encode($users);
        exit;
    }

    // Obtener historial médico (A01: Sin control de acceso)
    if ($_POST['action'] == 'medical_history') {
        header('Content-Type: application/json');
        
        $patient_id = $_POST['patient_id'] ?? '';
        
        // A03: SQL Injection vulnerable
        $query = "SELECT * FROM medical_history WHERE patient_id='$patient_id' ORDER BY visit_date DESC";
        $result = $mysqli->query($query);
        $history = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        
        echo json_encode($history);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Historiales Clínicos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .login-form, .search-form {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        .patient-record {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .error {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 5px;
        }
        .success {
            color: green;
            margin: 10px 0;
            padding: 10px;
            background: #e8f5e8;
            border-radius: 5px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: #ddd;
            cursor: pointer;
            border: 1px solid #ccc;
            margin-right: 5px;
        }
        .tab.active {
            background: #3498db;
            color: white;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        pre {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
        .medical-history {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
        }
        .vulnerability-info {
            background: #ffebee;
            border: 1px solid #f44336;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Sistema de Historiales Clínicos</h1>
            <p>Gestión de Pacientes - Clínica San Rafael</p>
            <p><small>Servidor Web: 192.168.100.50 | Base de Datos: 192.168.100.60</small></p>
        </div>

        <div class="vulnerability-info">
            <h4>⚠️ Aplicación Vulnerable - Solo para Pruebas</h4>
            <p>Esta aplicación contiene vulnerabilidades intencionales del OWASP Top 10 2021 para fines educativos.</p>
        </div>

        <!-- Información de sesión -->
        <div id="sessionInfo" style="background: #d4edda; padding: 10px; margin-bottom: 20px; border-radius: 5px; display: none;">
            <strong>Sesión activa:</strong> <span id="currentUser"></span> (<span id="currentRole"></span>)
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="showTab('login')">Iniciar Sesión</div>
            <div class="tab" onclick="showTab('search')">Buscar Pacientes</div>
            <div class="tab" onclick="showTab('admin')">Administración</div>
        </div>

        <!-- Login Tab -->
        <div id="login" class="tab-content active">
            <div class="login-form">
                <h3>Iniciar Sesión</h3>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit">Iniciar Sesión</button>
                </form>
                <div id="loginMessage"></div>
                
                <div style="margin-top: 20px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                    <h4>Credenciales de prueba:</h4>
                    <p><strong>Admin:</strong> admin / 123456</p>
                    <p><strong>Doctor:</strong> doctor1 / password</p>
                    <p><strong>Enfermera:</strong> nurse1 / nurse123</p>
                    <p><strong>Invitado:</strong> guest / guest</p>
                </div>
            </div>
        </div>

        <!-- Search Tab -->
        <div id="search" class="tab-content">
            <div class="search-form">
                <h3>Buscar Historiales de Pacientes</h3>
                <form id="searchForm">
                    <div class="form-group">
                        <label for="patientId">ID del Paciente:</label>
                        <input type="text" id="patientId" name="patientId" placeholder="Ej: 001">
                    </div>
                    <div class="form-group">
                        <label for="patientName">Nombre del Paciente:</label>
                        <input type="text" id="patientName" name="patientName" placeholder="Ej: María">
                    </div>
                    <div class="form-group">
                        <label for="doctorFilter">Filtrar por Doctor:</label>
                        <select id="doctorFilter" name="doctorFilter">
                            <option value="">Todos los doctores</option>
                            <option value="Dr. García">Dr. García</option>
                            <option value="Dra. López">Dra. López</option>
                            <option value="Dr. Martínez">Dr. Martínez</option>
                        </select>
                    </div>
                    <button type="submit">Buscar</button>
                    <button type="button" onclick="showAllPatients()">Ver Todos</button>
                </form>
            </div>
            <div id="searchResults"></div>
        </div>

        <!-- Admin Tab -->
        <div id="admin" class="tab-content">
            <div class="login-form">
                <h3>Panel de Administración</h3>
                <p><strong>Funciones administrativas (Sin control de acceso):</strong></p>
                <button onclick="exportData()">Exportar Datos</button>
                <button onclick="viewLogs()">Ver Logs del Sistema</button>
                <button onclick="manageUsers()">Gestionar Usuarios</button>
                <div id="adminResults"></div>
            </div>
        </div>
    </div>

    <script>
        let currentUser = null;

        function showTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            const tabButtons = document.querySelectorAll('.tab');
            tabButtons.forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        // Login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('username', document.getElementById('username').value);
            formData.append('password', document.getElementById('password').value);
            
            document.getElementById('loginMessage').innerHTML = '<div class="loading">Iniciando sesión...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error HTTP: ' + response.status);
                }
                return response.text(); // Primero como texto para debug
            })
            .then(text => {
                console.log('Respuesta del servidor:', text); // Para debug
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('loginMessage').innerHTML = 
                            `<div class="success">${data.message}</div>`;
                        document.getElementById('sessionInfo').style.display = 'block';
                        document.getElementById('currentUser').textContent = data.user.username;
                        document.getElementById('currentRole').textContent = data.user.role;
                        currentUser = data.user;
                    } else {
                        document.getElementById('loginMessage').innerHTML = 
                            `<div class="error">${data.message}</div>`;
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    document.getElementById('loginMessage').innerHTML = 
                        `<div class="error">Error de respuesta del servidor. Ver consola para detalles.</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loginMessage').innerHTML = 
                    `<div class="error">Error de conexión: ${error.message}</div>`;
            });
        });

        // Búsqueda de pacientes
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'search');
            formData.append('patient_id', document.getElementById('patientId').value);
            formData.append('patient_name', document.getElementById('patientName').value);
            formData.append('doctor_filter', document.getElementById('doctorFilter').value);
            
            document.getElementById('searchResults').innerHTML = '<div class="loading">Buscando...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('searchResults').innerHTML = 
                    `<div class="error">Error al buscar: ${error.message}</div>`;
            });
        });

        function showAllPatients() {
            const formData = new FormData();
            formData.append('action', 'show_all');
            
            document.getElementById('searchResults').innerHTML = '<div class="loading">Cargando pacientes...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                displayResults(data);
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('searchResults').innerHTML = 
                    `<div class="error">Error al cargar: ${error.message}</div>`;
            });
        }

        function displayResults(results) {
            const resultsDiv = document.getElementById('searchResults');
            
            if (!results || results.length === 0) {
                resultsDiv.innerHTML = '<p>No se encontraron resultados.</p>';
                return;
            }
            
            let html = '<h3>Resultados de la búsqueda (' + results.length + ' pacientes):</h3>';
            results.forEach(patient => {
                html += `
                    <div class="patient-record">
                        <h4>📋 Paciente: ${patient.name}</h4>
                        <p><strong>ID:</strong> ${patient.id}</p>
                        <p><strong>Edad:</strong> ${patient.age} años</p>
                        <p><strong>Diagnóstico:</strong> ${patient.diagnosis}</p>
                        <p><strong>Doctor:</strong> ${patient.doctor}</p>
                        <p><strong>SSN:</strong> ${patient.ssn}</p>
                        <p><strong>Teléfono:</strong> ${patient.phone}</p>
                        <p><strong>Dirección:</strong> ${patient.address}</p>
                        <p><strong>Notas:</strong> ${patient.notes}</p>
                        <p><strong>Última visita:</strong> ${patient.last_visit}</p>
                        <button onclick="viewMedicalHistory('${patient.id}')">Ver Historial Médico</button>
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        }

        function viewMedicalHistory(patientId) {
            const formData = new FormData();
            formData.append('action', 'medical_history');
            formData.append('patient_id', patientId);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let html = `<h4>📋 Historial Médico - Paciente ${patientId}:</h4>`;
                if (data && data.length > 0) {
                    data.forEach(record => {
                        html += `
                            <div class="medical-history">
                                <p><strong>📅 Fecha:</strong> ${record.visit_date}</p>
                                <p><strong>🩺 Síntomas:</strong> ${record.symptoms}</p>
                                <p><strong>🏥 Tratamiento:</strong> ${record.treatment}</p>
                                <p><strong>💊 Medicación:</strong> ${record.medication}</p>
                                <p><strong>👨⚕️ Doctor:</strong> ${record.doctor}</p>
                                <p><strong>📝 Notas:</strong> ${record.notes}</p>
                            </div>
                        `;
                    });
                } else {
                    html += '<p>No se encontró historial médico para este paciente.</p>';
                }
                
                document.getElementById('searchResults').innerHTML += html;
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function exportData() {
            const formData = new FormData();
            formData.append('action', 'export');
            
            document.getElementById('adminResults').innerHTML = '<div class="loading">Exportando datos...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('adminResults').innerHTML = 
                    `<h4>📊 Datos exportados (${data.length} registros):</h4><pre>${JSON.stringify(data, null, 2)}</pre>`;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('adminResults').innerHTML = 
                    `<div class="error">Error al exportar: ${error.message}</div>`;
            });
        }

        function viewLogs() {
            const formData = new FormData();
            formData.append('action', 'logs');
            
            document.getElementById('adminResults').innerHTML = '<div class="loading">Cargando logs...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let html = `<h4>📋 Logs del sistema (${data.length} registros):</h4><pre>`;
                data.forEach(log => {
                    html += `${log.timestamp} - User:${log.user_id} - ${log.action} - IP:${log.ip_address}`;
                    if (log.details) html += ` - ${log.details}`;
                    html += '\n';
                });
                html += '</pre>';
                document.getElementById('adminResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('adminResults').innerHTML = 
                    `<div class="error">Error al cargar logs: ${error.message}</div>`;
            });
        }

        function manageUsers() {
            const formData = new FormData();
            formData.append('action', 'users');
            
            document.getElementById('adminResults').innerHTML = '<div class="loading">Cargando usuarios...</div>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let html = `<h4>👥 Usuarios del sistema (${data.length} usuarios):</h4>`;
                html += '<div style="background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0;"><strong>⚠️ VULNERABILIDAD A02:</strong> Contraseñas mostradas en texto plano</div>';
                data.forEach(user => {
                    html += `<p><strong>🔑 ${user.username}</strong> - Password: <code>${user.password}</code> - Role: ${user.role} - Email: ${user.email}</p>`;
                });
                document.getElementById('adminResults').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('adminResults').innerHTML = 
                    `<div class="error">Error al cargar usuarios: ${error.message}</div>`;
            });
        }
    </script>
</body>
</html>
