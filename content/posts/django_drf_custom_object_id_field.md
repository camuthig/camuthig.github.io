---
title: "Building Custom Django Fields"
date: 2020-05-10
tags: ["python", "django", "restframework"]
summary: "
I recently needed to implement a special kind of field in Django Rest Framework that turned out to be a fun
experiment. It includes per-request-format representation and altering how relationships are represented.
"
---

I recently needed to implement a special kind of field in Django Rest Framework that turned out to be a fun
experiment. I recreated this in a simple Django application, and the bulk of the DRF-related code can be found
[here](https://github.com/camuthig/django-drf-custom-id-object-field/blob/3e753153e50a35be5795858db3e852d72d7f34a8/bookstore/serializers.py#L9-L83).

First, the API used a hashed ID. So the primary key from the database was taken, additional information added
to it and then hashed.

Second, the API to *set* the relationship value behaved differently than when reading it. When sending a `POST`
or `PUT` value, the API client would always send an object with the `id` key and the hashed ID of the
relationship. This is different than most of the existing relationship fields in DRF, where the field is
a scalar of some type. Along with this custom write pattern, on read operations the nested object was expected
to be rendered with all fields.

An example is helpful to see the pattern. In this example we have two models, `Author` and `Book`, where a book
has one `Author` relationship.

Creating a new book would look something like

```json
{
    "title": "Awesome new book",
    "author": {
        "id": "hashed1",
    }
}
```

And the response from this would be something like the below. You can see that by having the API send the ID as part
of an object, instead of as a scalar, the request and response begin to look similar, which is pretty cool.

```json
{
    "title": "Awesome new book",
    "author": {
        "id": "hashed1",
        "name": "Arthur Writes"
    }
}
```

## Implementation

The full project can be found [here](https://github.com/camuthig/django-drf-custom-id-object-field).

Most of the work was around creating new field types for these hashed IDs as well as one specifically for wrapping it
in an object.

The first part was pretty easy, getting the IDs into a hashed format. This was two classes, one used for the ID on the
current object and the other used for the relationship fields.

```python
class HashedPrimaryKeyField(serializers.CharField):
    """
    A field representing the primary key of an object as a base64 encoded string.
    """
    def to_internal_value(self, data):
        return base64.urlsafe_b64decode(data).decode('utf-8')

    def to_representation(self, value):
        return base64.urlsafe_b64encode(bytes(f'{value}', 'utf-8')).decode('utf-8')


class HashedPrimaryKeyRelatedField(serializers.PrimaryKeyRelatedField):
    """
    A relationship field representing the primary key of a related field as a base64 encoded string.
    """
    def to_internal_value(self, data):
        data = base64.urlsafe_b64decode(data).decode('utf-8')
        return super().to_internal_value(data)

    def to_representation(self, value):
        if value is None:
            return value

        return base64.urlsafe_b64encode(bytes(f'{value.pk}', 'utf-8')).decode('utf-8')
```

The next challenge was to determine the format which the user requested. I left this up to the DRF content negotiation.
The negotiation sets an `accepted_renderer` parameter on the request before passing it down to the serializer in the
`APIView`. This means determining the format is as easy as

```python
    def _format(self) -> Optional[str]:
        request = self.context.get('request')

        return request.accepted_renderer.format

    def _accepts_json(self):
        return 'json' == self._format()
```

Next, I updated the validation and representational logic for my new field. Because my representation uses only the
ID when building a non-JSON request, I turned on the PK only optimization in those cases and turned it off otherwise.

```python
    def run_validation(self, data=None):
        if self._is_json() and data is not None:
            if not isinstance(data, dict):
                self.fail('id_missing')
            if 'id' not in data:
                self.fail('id_missing')

        return super().run_validation(data=data)

    def use_pk_only_optimization(self):
        if self._accepts_json:
            return False

        return True

    def to_internal_value(self, data):
        if self._is_json() and data is not None:
            data = data['id']

        return super().to_internal_value(data)

    def to_representation(self, value):
        if self._accepts_json():
            # I pass the serializer class to the field on construction to make this possible
            return self.serializer(instance=value).data

        return super().to_representation(value)
```

Finally, I updated my serializers to use the new fields.

```python
class ModelSerializer(serializers.ModelSerializer):
    id = HashedPrimaryKeyField(read_only=True)


class AuthorSerializer(ModelSerializer):
    class Meta:
        model = models.Author
        fields = ['id', 'name']


class BookSerializer(ModelSerializer):
    class Meta:
        model = models.Book
        fields = ['id', 'title', 'author']

    author = NestedHashedPrimaryKeyRelatedField(queryset=models.Author.objects, serializer=AuthorSerializer)
```