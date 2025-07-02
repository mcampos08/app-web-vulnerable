pipeline {
    agent any

    environment {
        GITHUB_REPO = 'https://github.com/mcampos08/app-web-vulnerable.git'
    }

    stages {
        stage('📥 Checkout') {
            steps {
                echo '=== CLONANDO REPOSITORIO ==='
                git branch: 'main', url: "${GITHUB_REPO}"
            }
        }

        stage('📦 Análisis SCA - OWASP Dependency Check') {
            steps {
                echo '=== ANALIZANDO src/ CON OWASP (uso correcto de tool) ==='
                script {
                    def dcHome = tool name: 'OWASP-Dependency-Check', type: 'org.jenkinsci.plugins.DependencyCheck.tools.DependencyCheckInstallation'
                    sh """
                        mkdir -p reports/dependency-check

                        ${dcHome}/bin/dependency-check.sh \
                          --project "owasp-app" \
                          --scan . \
                          --format HTML \
                          --out reports/dependency-check \
                          --enableRetired \
                          --data ${WORKSPACE}/.dependency-check-data \
                          --log reports/dependency-check/owasp-sca.log

                        echo "✅ Reporte generado en reports/dependency-check"
                    """
                }
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: 'reports/dependency-check/dependency-check-report.html', onlyIfSuccessful: true

            publishHTML([
                reportName: 'Reporte OWASP Dependency-Check',
                reportDir: 'reports/dependency-check',
                reportFiles: 'dependency-check-report.html',
                keepAll: true,
                allowMissing: false,
                alwaysLinkToLastBuild: true
            ])
        }


        success {
            echo '🎉 ANÁLISIS DE DEPENDENCIAS COMPLETADO CON ÉXITO'
        }

        failure {
            echo '❌ FALLÓ EL ANÁLISIS DE DEPENDENCIAS - VERIFICAR LOGS'
        }
    }
}
