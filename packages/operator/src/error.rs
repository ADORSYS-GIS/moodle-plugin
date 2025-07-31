
#[derive(Debug, thiserror::Error)]
pub enum Error {
    #[error("Failed to create replicaset: {0}")]
    ReplicaSetCreationFailed(#[from] kube::Error),
    
    #[error("Failed to create pv: {0}")]
    PersistenceVolumeCreationFailed(String),
    
    #[error("Failed to get Moodle CR: {0}")]
    MoodleCRGetFailed(kube::Error),
    
    #[error("Failed to get ReplicaSet: {0}")]
    ReplicaSetGetFailed( kube::Error),
    
    #[error("Failed to patch Moodle status: {0}")]
    StatusPatchFailed( kube::Error),
    
    #[error("Moodle resource has no namespace")]
    MissingNamespace,
}
