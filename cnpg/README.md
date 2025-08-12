./cnpg/install-operator.sh

kubectl create secret generic moodle-db-secret \
  --from-literal=username=moodle \
  --from-literal=mariadb-password=moodlepass \
  --from-literal=password=moodlepass \
  -n moodle


kubectl apply -f cnpg/postgres-cluster.yaml

# install Moodle
helm install my-moodle . -n moodle -f moodle/values.yaml
