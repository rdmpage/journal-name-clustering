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

### Bad names

The Taxonworks data is messy, journal names such as “p” will cause spurious clusters as everything with the sort key “p” is potentially part of that cluster. Hence we remove those from the data.

### Clustering

The journal names are cleaned of extraneous characters, split into tokens, and the first letter of each token is extracted. These letters are concatenated to create a “sort key” for each name.

As a first pass we cluster using the sort key to “block” the data, that is, we use the sort key to subset the data into manageable chuncks. Journal names with the same sort key are then  clustered based on the similarity of their cleaned, tokenised names. We use a [disjoint set](https://en.wikipedia.org/wiki/Disjoint-set_data_structure) to hold the clusters.

To cluster the journals run:

```
php cluster.php > <output filename>
```

which output the SQL commands to update the `clusters` table.

### Output

The query `SELECT * FROM clusters ORDER BY sort key, cluster_id;` generates a list of clustered journal names. This list has been used to create a Google Sheet, with an additional column that, together with conditional formatting, makes any additional names in a cluster a different colour, making the clustering more visible.

The Google Sheet is https://docs.google.com/spreadsheets/d/1130fgDii0WodZIookBOg-KICZAdlk6lBVb1iTENPcr4/edit?usp=sharing

