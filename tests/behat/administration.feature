@local @local_sanitizedclone
Feature: Administrators can preview without executing database sanitization over HTTP
  In order to prepare an isolated clone
  As a site administrator
  I can inspect the dashboard and create profiles safely

  Background:
    Given I log in as "admin"

  @javascript
  Scenario: Administrator sees the safety warning and creates a reusable profile
    When I am on site homepage
    And I navigate to "Server > Sanitized clone" in site administration
    Then I should see "Danger: do not run against production"
    When I set the field "Profile name" to "Preview fixture"
    And I press "Create fixed-policy profile"
    Then I should see "Preview fixture"
    And I should see "Not analyzed (blocks approval)"
