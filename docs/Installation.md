
# Installation

**1. Clone the sourcecode**
```
git clone https://github.com/NaspersClassifieds/tradus-bundle.git
```

**2. Get in there**
```
cd tradus-bundle/
```

**3. Tagging**
We version the `tradus-bundle` repository in order to use as composer package:
```
[DO YOUR CHANGES]
git add -A
git commit -m "TRAD-XXX - xxxx"
git tag -a [tag_version]
git push origin [tag_version]
```
After this, go to the repository that use the bundle and:
```
cd ../tradus-api-front
composer update naspersclassifieds/tradus-bundle
```

