pipeline {
    agent any
    
    environment {
        VM_STAGING_IP = '192.168.72.128'
        STAGING_USER = 'clindata'
        STAGING_PATH = '/var/www/html/clindata'
        GITHUB_REPO = 'https://github.com/mcampos08/app-web-vulnerable.git'
        SONARQUBE_SERVER = 'SonarQube-Local'
    }
    
    stages {
        stage('🔧 Test Básico') {
            steps {
                echo '=== PRUEBA RÁPIDA DE HERRAMIENTAS ==='
                
                // Verificar herramientas básicas
                sh '''
                    echo "Java: $(java -version 2>&1 | head -1)"
                    echo "Git: $(git --version)"
                    echo "Curl: $(curl --version | head -1)"
                '''
                
                // Verificar SonarQube Scanner
                script {
                    try {
                        def scannerHome = tool 'SonarQubeScanner'
                        echo "✅ SonarQube Scanner: ${scannerHome}"
                    } catch (Exception e) {
                        echo "❌ SonarQube Scanner: ${e.message}"
                    }
                }
                
                               
                // Verificar Syft
                sh '''
                    if command -v syft &> /dev/null; then
                        echo "✅ Syft: $(syft version)"
                    else
                        echo "❌ Syft no encontrado"
                    fi
                '''
            }
        }
        
        stage('📥 Checkout Test') {
            steps {
                echo '=== CLONANDO REPOSITORIO ==='
                git branch: 'main', url: "${GITHUB_REPO}"
                
                sh '''
                    echo "Commit: $(git log -1 --oneline)"
                    echo "Archivos: $(ls -la | wc -l) archivos encontrados"
                    echo "PHP files: $(find . -name "*.php" | wc -l)"
                '''
            }
        }
        
        stage('🔍 SonarQube Rápido') {
            steps {
                echo '=== PRUEBA RÁPIDA SONARQUBE ==='
                script {
                    // Solo verificar conexión
                    sh '''
                        echo "Verificando SonarQube..."
                        curl -s --connect-timeout 5 http://localhost:9000/api/system/status | grep -q "UP" && echo "✅ SonarQube UP" || echo "❌ SonarQube DOWN"
                    '''
                    
                    // Análisis muy básico con timeout
                    try {
                        timeout(time: 3, unit: 'MINUTES') {
                            def scannerHome = tool 'SonarQubeScanner'
                            withSonarQubeEnv("${SONARQUBE_SERVER}") {
                                sh """
                                    ${scannerHome}/bin/sonar-scanner \
                                        -Dsonar.projectKey=quick-test \
                                        -Dsonar.projectName='Quick Test' \
                                        -Dsonar.sources=. \
                                        -Dsonar.exclusions=**/*.log,**/*.md
                                """
                            }
                        }
                        echo "✅ SonarQube análisis completado"
                    } catch (Exception e) {
                        echo "⚠️ SonarQube timeout o error: ${e.message}"
                    }
                }
            }
        }
        
        stage('🔗 SSH Test') {
            steps {
                echo '=== PRUEBA SSH ==='
                script {
                    try {
                        sshagent(['staging-ssh-key']) {
                            sh '''
                                ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 ${STAGING_USER}@${VM_STAGING_IP} "
                                    echo 'SSH OK - $(date)'
                                    uptime
                                "
                            '''
                        }
                        echo "✅ SSH funcionando"
                    } catch (Exception e) {
                        echo "❌ SSH error: ${e.message}"
                    }
                }
            }
        }
    }
    
    post {
        always {
            echo '''
            =================================
            RESUMEN DE PRUEBA RÁPIDA
            =================================
            ✅ = Funcionando correctamente
            ❌ = Necesita configuración
            ⚠️ = Funciona pero con warnings
            
            Revisar logs para detalles específicos
            '''
        }
        
        success {
            echo '🎉 PRUEBA BÁSICA COMPLETADA - LISTO PARA PIPELINE COMPLETO'
        }
        
        failure {
            echo '🔧 HAY PROBLEMAS DE CONFIGURACIÓN - REVISAR HERRAMIENTAS'
        }
    }
}
