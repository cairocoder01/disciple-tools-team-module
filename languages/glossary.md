# Team Module Glossary

Common terms used in the Disciple.Tools Team Module plugin.

## Core Concepts

| Term | Description |
|------|-------------|
| **Team** | A group of users who collaborate together and share access to contacts, groups, and other records. |
| **Teams** | Plural form of Team; the post type used to manage team records. |
| **Members** | Contacts who belong to a specific team. |
| **Member of Teams** | The relationship field on a contact showing which teams they belong to. |

## User Roles

| Term | Description |
|------|-------------|
| **Team Member** | A user role that can interact with contacts, groups, and trainings for their assigned team(s). Has access to records assigned to their team. |
| **Team Collaborator** | A user role with access to all contacts, groups, and trainings across all teams. |
| **Team Leader** | A user role with full access to all contacts, groups, and trainings, plus the ability to update their own team's settings. |
| **Teams Admin** | An administrative role with full access to create, view, and update all teams and their records. |

## Features

| Term | Description |
|------|-------------|
| **Magic Link** | A secure, shareable URL that provides external access to team-related data without requiring login. |
| **List Team Contacts** | A magic link template type that displays all contacts assigned to the user's team(s). |
| **Team Contacts** | Contacts that are assigned to or associated with a specific team. |

## Permissions

| Term | Description |
|------|-------------|
| **access_specific_teams** | Permission that grants a user access to all posts their team(s) are assigned to. |
| **list_all_teams** | Permission to view all teams in the system. |
| **view_any_teams** | Permission to view any team record. |
| **update_any_teams** | Permission to update any team record. |
| **update_my_teams** | Permission to update only teams the user is a member of. |

## Fields

| Term | Description |
|------|-------------|
| **teams** | A connection field added to all post types (contacts, groups, etc.) to associate records with teams. |
| **members** | A connection field on the Team post type linking to contact records who are team members. |
