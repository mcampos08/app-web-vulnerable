pipeline {
    agent any

    environment {
        SYFT_OUTPUT = 'sbom.json'
        GRYPE_REPORT = 'grype-report.json'
        GRYPE_SARIF = 'grype-report.sarif'
        BUILD_SHOULD_FAIL = 'false'
    }

    stages {
        stage('Clonar código') {
            steps {
                git url: 'https://github.com/mcampos08/app-web-vulnerable.git', branch: 'main'
            }
        }

        stage('Instalar dependencias PHP') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }

        stage('Generar SBOM con Syft') {
            steps {
                sh "syft dir:. -o json > ${SYFT_OUTPUT}"
            }
        }

        stage('Análisis con Grype') {
            steps {
                sh """
                    set +e
                    grype sbom:${SYFT_OUTPUT} -o json > ${GRYPE_REPORT}
                    grype sbom:${SYFT_OUTPUT} -o sarif > ${GRYPE_SARIF}
                    grype sbom:${SYFT_OUTPUT} -o table --fail-on high
                    if [ \$? -ne 0 ]; then
                        echo "❌ Vulnerabilidades altas o críticas encontradas"
                        echo "BUILD_SHOULD_FAIL=true" > grype.fail
                    fi
                """
            }
        }
    }

    post {
        always {
            archiveArtifacts artifacts: '*.json', fingerprint: true
            recordIssues(tools: [ sarif(pattern: 'grype-report.sarif') ])

            // Revisar si el análisis encontró vulnerabilidades graves
            script {
                if (fileExists('grype.fail')) {
                    currentBuild.result = 'FAILURE'
                    echo '⚠️ El análisis encontró vulnerabilidades críticas. Build marcado como FAILURE.'
                } else {
                    echo '✅ Sin vulnerabilidades críticas detectadas.'
                }
            }
        }
    }
}
