#[derive(Debug, thiserror::Error)]
pub enum Error {
    #[error("Failed to create replicaset: {0}")]
    ReplicaSetCreationFailed(#[from] kube::Error),

    #[error("Failed to get ReplicaSet: {0}")]
    ReplicaSetGetFailed(kube::Error),
}

impl Error {
    pub fn is_not_found(&self) -> bool {
        match self {
            Error::ReplicaSetGetFailed(kube::Error::Api(api_err)) => matches!(api_err.code, 404),
            _ => false,
        }
    }
}
