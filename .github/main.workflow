workflow "k8s Auto Deploy" {
  on = "push"
  resolves = [
    "Auto DevOps",
  ] 
}

# Configure files
action "Configure WordPress files" {
  uses = "./.github/configure"
  env = {
    DEPLOY_BRANCH = "dep-dev"
    DEPLOY_WORKFLOW = "k8s Auto Deploy"
  }
}

# GKE
action "Setup Google Cloud" {
  needs = ["Configure WordPress files"]
  uses = "rtcamp/gcloud/auth@master"
  env = {
    VAULT_FIELD = "dev-server"
    VAULT_PATH = "secret/k8s-gcloud"
  }
  secrets = ["VAULT_ADDR", "VAULT_TOKEN"]
}

# Build
action "Build Docker image" {
  needs = ["Configure WordPress files"]
  uses = "actions/docker/cli@master"
  args = ["build", "-t", "laterpay-wordpress-plugin", ".github/php-fpm"]
}

action "Set Credential Helper for Docker" {
  needs = ["Setup Google Cloud"]
  uses = "actions/gcloud/cli@master"
  args = ["auth", "configure-docker", "--quiet"]
}

action "Load GKE kube credentials" {
  needs = ["Setup Google Cloud"]
  uses = "actions/gcloud/cli@master"
  env = {
    PROJECT_ID = "rtcamp-default"
    CLUSTER_NAME = "rt-cluster-dev"
  }
  args = "container clusters get-credentials $CLUSTER_NAME --zone us-east1-b --project $PROJECT_ID"
}

action "Tag image for GCR" {
  needs = ["Setup Google Cloud", "Build Docker image"]
  uses = "actions/docker/tag@master"
  env = {
    PROJECT_ID = "rtcamp-default"
    APPLICATION_NAME = "laterpay-wordpress-plugin"
  }
  args = ["$APPLICATION_NAME", "gcr.io/$PROJECT_ID/$APPLICATION_NAME"]
}

action "Push image to GCR" {
  needs = ["Setup Google Cloud", "Set Credential Helper for Docker", "Tag image for GCR"]
  uses = "actions/gcloud/cli@master"
  runs = "sh -c"
  env = {
    PROJECT_ID = "rtcamp-default"
    APPLICATION_NAME = "laterpay-wordpress-plugin"
  }
  args = ["docker push gcr.io/$PROJECT_ID/$APPLICATION_NAME"]
}

action "test-kubectl" {
  needs = ["Load GKE kube credentials"]
  uses = "docker://gcr.io/cloud-builders/kubectl"
  runs = "sh -l -c"
  args = ["kubectl config view"]
}

action "Auto DevOps" {
  needs = ["Push image to GCR", "Load GKE kube credentials","test-kubectl"]
  uses = "./.github/auto-devops"
  env = {
    PROJECT_ID = "rtcamp-default"
    APPLICATION_NAME = "laterpay-wordpress-plugin"
    ORG_NAME = "rtCamp"
    SITE_URL = "laterpay"
    BASE_URL = "dev.rt.gw"
    MU = "none"
  }
  secrets = ["VAULT_ADDR", "VAULT_TOKEN"]
}
action "Slack Notification" {
  needs = ["Auto DevOps"]
  uses = "./.github/action-slack-notify"
  env = {
    SLACK_CHANNEL = "test",
    CLUSTER_NAME = "rt-cluster-dev"
  }
  secrets = ["VAULT_ADDR", "VAULT_TOKEN"]
}
