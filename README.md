# Journal Name Clustering

Clustering scholarly journals by their name.

## Taxonworks serials

https://github.com/SpeciesFileGroup/taxonworks/issues/4462

serials_2025-06-16T20_16_58+00_00.tsv

Read TSV file and output with cleaned names as a “sort key” based on first letter of each non-trivial word in the name.

```
php import.php data/serials_2025-06-16T20_16_58+00_00.tsv > clusters.csv
```

Create a SQLite database `containers` and import the cleaned journal names into a table called `clusters`.

