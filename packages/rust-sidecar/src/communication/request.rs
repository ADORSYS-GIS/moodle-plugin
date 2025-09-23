use serde::{Deserialize, Serialize};

#[derive(Serialize, Deserialize, Debug, Clone)]
pub struct Request {
    pub action: String,
    pub content: String,
    pub context: Option<String>,
    pub user_id: Option<String>,
}

impl Request {
    pub fn new(action: String, content: String) -> Self {
        Self {
            action,
            content,
            context: None,
            user_id: None,
        }
    }
    
    pub fn with_context(mut self, context: String) -> Self {
        self.context = Some(context);
        self
    }
    
    pub fn with_user_id(mut self, user_id: String) -> Self {
        self.user_id = Some(user_id);
        self
    }
}
