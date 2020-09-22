# DGI IR Spreadsheet Ingest Example

## Introduction

A template of migration .yml files designed to import all fields of the base
Research Output content type from a CSV file.

## Requirements

This module requires the following modules/libraries:

* [islandora_spreadsheet_ingest](https://github.com/discoverygarden/islandora_spreadsheet_ingest/tree/8.x)
* [dgi_ir](https://github.com/discoverygarden/dgi_ir.git)

## Usage

### Modifications to the migration

Installing this module will give you out-of-the-box support for all the base
fields implemented by the Research Output content type.

If you want to make modifications to this, you should grab all the .yml files
in `config/install`, modify them, then add them all to a .zip file and upload
the copy to `admin/structure/migrate_templates`.

Similarly, your filled-out CSV should be uploaded to
`admin/structure/islandora_spreadsheet_ingest` and use the template you created.

Writing Migration API .yml files is a complicated process; it would be prudent
to look over the
[API overview](https://www.drupal.org/docs/8/api/migrate-api/migrate-api-overview)
before making any modifications.

**It is not recommended to directly modify the contents of this module** as
opposed to taking the files and reuploading modifications. In addition to drift
issues, changes also will not be recognized until the module is reinstalled,
which can cause issues with data not properly rolled back.

### References to rows, taxonomy terms, and nodes.

Some columns can refer to other rows by their `id`; this will be noted in the
list of columns section below.

There is not (currently) a taxonomy term migration, though one could be created
that the node migration would then be dependent on. In the columns listed in the
section below, some of them create new terms if necessary, whereas others don't.
It follows the same entity creation rules as the base Research Output creation
form. If you need terms to be in place for nodes being migrated, they should -
as of writing - be created before the migration is run.

Some columns will refer to entities by their identifier in Drupal; this is
generally used for more complicated fields that store multiple values. This will
be noted in the list of columns section below.

### Notes about CSV columns

Most of the columns in the .csv are fairly cut-and-dry; however, there are a few
of note which, if used out-of-the-box, require some special consideration.

Collection rows must have a `node_type` of `collection`; other than that, they
only require the `id` and `title` columns to be filled out, and can optionally
use the `member_of` and `abstract` fields as well.

Research output rows must have a `node_type` of `research_output`. They also
require the `id`, `title`, `abstract`, `access_status`, and
`research_output_type` columns to be filled out.

Column Name|Notes
-----------|-----
`id`|A unique, arbitrary identifier for the row. This can be referenced in other rows, as will be described below. Since the `member_of` column can reference existing collections as well as row `id`s, it's best to use identifiers that won't clash with existing collection titles.
`node_type`|Must be one of either 'collection', if the row represents a collection, or 'research_output', if the row represents a research output node.
`member_of`|Can be used to reference a collection in a different row by its `id`, or an existing node in Drupal by its `title`. In the event of a tie, preference will be given to a row `id` in the spreadsheet. This is currently single-valued.
`digital_file`|The path to a file relative to the migration templates. This can be customized, however; if you check `config/install/migrate_plus.migration.research_output_files.yml` you can see exactly where it's looking for files. Modifying this in your own copy would produce a different source folder for digital files. These files are attached as Media to the item created by the row.
`model`|One of 'image', 'file', 'document', 'video', 'audio', or 'binary', depending on the file type; this defines what kind of Media is created.
`mimetype`|The mimetype to be applied to the file
`access_status`|The name of one of the terms in the IR Access Status taxonomy
`supervisor`|The names of one or more of the terms in the Scholars taxonomy, separated by a semicolon and space. If no such scholar exists, one will be created.
`affiliated_organizations`|The names of one or more of the terms in the Organizational Units taxonomy, separated by a semicolon and space. If no such term exists, one will be created.
`author`|The names of one or more of the terms in the Scholars taxonomy, separated by a semicolon and space. If no such scholar exists, it will not be added to the Scholars taxonomy.
`contributor_existing`|The names of one or more of the terms in the Organizational Units or Scholars taxonomies, separated by a semicolon and space.
`contributor_new`|The names of one or more terms to add to the Organizational Units taxonomy as a new contributor, separated by a semicolon and space.
`creator_existing`|The names of one or more of the terms in the Organizational Units or Scholars taxonomies, separated by a semicolon and space.
`creator_new`|The names of one or more terms to add to the Organizational Units taxonomy as a new contributor, separated by a semicolon and space.
`date_accepted`|An EDTF formatted date.
`date_copyrighted`|An EDTF formatted date.
`date_created`|An EDTF formatted date.
`date_published`|An EDTF formatted date.
`date_submitted`|An EDTF formatted date.
`degree_level`|The name of one of the terms in the Degree Levels taxonomy. If no such Degree Level exists, one will not be created.
`external_references`|One or more links to an external reference, separated by a semicolon and space. Each external reference takes the form of a URI and Title, separated by a pipe (i.e., \|). For example: `http://first.uri.com\|First URI; http://second.uri.com\|Second URI`.
`tags`|The names of one or more terms from the Tags taxonomy, separated by a semicolon and space. If no such term exists, one will be created.
`license`|The name of one of the terms in the Creative Commons Licenses 4.0 taxonomy. If no such license exists, it will not be created.
`location`|The names of one or more of the terms in the Geographic Location taxonomy. If no such location exists, one will be created.
`member_of`|The `id` of a different row in the spreadsheet that should be used as a parent collection for this row.
`other_roles`|One or more other roles, separated by a semicolon and space. Each other role takes the form of a Taxonomy ID and Relationship, separated by two pipes (i.e., \|\|). The Taxonomy ID should be the ID of a taxonomy term from the Organizational Units or Scholars taxonomies. The Relationship must be one of the Relationship Types from the Research Output "Other Roles" field. For example: `45\|\|Editor; 27\|\|Producer`
`place_of_publication`|One of the terms in the Geographic Location taxonomy. If no such location exists, one will be created.
`related_item`|One or more related items already in the Drupal site, separated by a semicolon and space. Each related item takes the form of a Research Output ID and Relationship, separated by two pipes (i.e., \|\|). The Research Output ID must be the node ID of a Research Output item already in Drupal. The Relationship must be one of the Relationship Types from the Research Output "Related Repository Content" field. For example: `12\|\|isBasedOn; 51\|\|isPartOf`
`release_date`|An EDTF formatted date.
`research_output_type`|The name of one of the terms in the Research Output Type taxonomy. If no such type exists, one will not be created.
`rights_statement`|The name of one of the terms in the Rights Statements taxonomy. If no such rights statement exists, one will not be created.
`subject`|The names of one or more of the terms in the Subjects taxonomy, separated by a semicolon and space. If no such subject exists, one will be created.
`type`|The name of one of the terms in the DCMI Types taxonomy. If no such type exists, one will not be created.

### Notes about 'separated by' (i.e., delimiters)

There are three types of delimiters used by default:

Delimiter|Usage
---------|-----
`; `|A semicolon and space is used generally to separate individual entries for multi-valued fields.
`\|`|A pipe is used generally to separate two parts of the contents of a field, when both parts are plain text.
`\|\|`|A double pipe is used generally to separate two parts of the contents of a field, when the first part of that field is meant to be an identifier.

When looking through the migration .yml files, you will see that each of these delimiters appears in a spot either called `delimiter` or `subdelimiter`. If your data would clash with any of these delimiters such that you can't use one (e.g., if you had organization names that contained a pipe), you can swap them out in the .yml files themselves for different delimiters.

### Media type mapping

Media type mapping is handled by the extension of the filename in the 'digital_file' column. Out of the box, the mapping follows the allowed file extensions for the different media types; `extracted_text` is not considered. `file` Media are created by default, if the extension doesn't exist in the map. This mapping can be modified in `config/install/migrate_plus.migration.research_output_media.yml`.

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

An example migration and cmd that can help with development is provided.
If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
