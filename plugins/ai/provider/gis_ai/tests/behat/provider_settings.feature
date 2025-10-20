Feature: Provider settings page
  Scenario: Visit provider settings
    Given I am on "/admin/settings.php?section=aiprovider_gis_ai"
    Then I should see "GIS AI Provider settings"
