# Ideas


Wikidata queries, use Wikispecies as the way to filter out irrelevant journals…

```
#Items with a Wikispecies sitelink
# illustrates sitelink selection, ";" notation
#title: Items with a Wikispecies sitelink

  SELECT *
  WHERE {
    ?article schema:about ?item .
    ?article schema:isPartOf <https://species.wikimedia.org/> .
    ?item wdt:P31 wd:Q5633421 .
    ?item wdt:P1476 ?title .
 
    
    OPTIONAL{
      ?item wdt:P236 ?issn .
    }

    OPTIONAL{
      ?item wdt:P7363 ?issnl .
    }
    
    OPTIONAL{
      ?item wdt:P1160 ?iso4 .
    }

    OPTIONAL{
      ?item wdt:P1160 ?iso4 .
    }
 
    OPTIONAL{
      ?item wdt:P1813 ?shortName .
    }
 
    OPTIONAL{
      ?item wdt:P10283 ?openalex .
    }

    OPTIONAL{
      ?item wdt:P243 ?oclc .
    }

        OPTIONAL{
      ?item wdt:P2007 ?zoobank .
    }

        OPTIONAL{
      ?item wdt:P2008 ?ipni .
    }

           OPTIONAL{
      ?item wdt:P4327 ?bhl .
    }

           OPTIONAL{
      ?item wdt:P5115 ?doaj .
    }
 
    OPTIONAL{
      ?item wdt:P8608 ?fatcat .
    }
 
    

    
  }
  LIMIT 200

```

## External sources with useful information

### OpenAlex 

https://openalex.org/sources/S4306505208

### DOAJ



## Ontologies

https://sparontologies.github.io/fabio/current/fabio.html#d4e1161
