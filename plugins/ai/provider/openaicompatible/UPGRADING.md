# aiprovider_openaicompatible Upgrade notes

## V next

### Added

- A new `gpt4o` model class has been added to support text generation.
- A new `gptimage1` model class has been added to support image generation via `gpt-image-1`.
  This model uses `output_format=png` instead of `response_format`, and maps Moodle quality values to the values expected by the API: 'standard' maps to 'medium' and 'hd' maps to 'high'. Image data is returned directly via `b64_json`.

### Changed

- Configuration precedence has been inverted: Action-level model and endpoint settings now correctly override the global provider-level defaults.


### Removed

- The `dalle3` model class has been removed in favor of the new `gpt-image-1` strategy.
- The global provider-level `model` setting has been removed entirely. All models must now be configured at the action level.