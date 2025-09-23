use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize, Debug)]
pub struct Response {
    pub success: bool,
    pub data: Option<String>,
    pub error: Option<String>,
}

impl Response {
    pub fn success(data: String) -> Self {
        Self {
            success: true,
            data: Some(data),
            error: None,
        }
    }
    
    pub fn error<T: Into<String>>(error: T) -> Self {
        Self {
            success: false,
            data: None,
            error: Some(error.into()),
        }
    }
}