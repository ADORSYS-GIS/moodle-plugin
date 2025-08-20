// Example supports both configurations:
// - With `--features tokio`: runs the async loader
// - Without it: prints a hint instead of failing to compile

#[cfg(feature = "tokio")]
use ai_core::model_loader::ModelLoader;
#[cfg(feature = "tokio")]
use ai_utils::config::Config;
#[cfg(feature = "tokio")]
use std::fs;
#[cfg(feature = "tokio")]
use std::path::PathBuf;

#[cfg(feature = "tokio")]
#[tokio::main(flavor = "current_thread")]
async fn main() -> anyhow::Result<()> {
    // Prepare a temporary directory with a fake model file
    let temp_dir = tempfile::tempdir()?;
    let base_path = temp_dir.path().to_path_buf();
    let model_path: PathBuf = base_path.join("model.gguf");
    fs::write(&model_path, b"fake-model-bytes")?;

    // Minimal config
    let mut config = Config::new();
    config.data["models"]["base_path"] = serde_json::json!(base_path.to_string_lossy());
    config.data["models"]["backend"] = serde_json::json!("onnx");

    // Load asynchronously
    let mut loader = ModelLoader::from_config(&config)?;
    let _model = loader.load_async(Some("model.gguf")).await?;

    println!("Async model loaded: {}", model_path.display());
    Ok(())
}

#[cfg(not(feature = "tokio"))]
fn main() {
    eprintln!(
        "Enable `--features tokio` to run this example:\n  cargo run -p ai-core --features tokio --example async_loader"
    );
}
