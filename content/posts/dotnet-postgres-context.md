---
title: ".NET Core Postgres Context"
date: 2018-12-05
tags: ["c#", ".net", "postgres"]
draft: true
---

I've been playing around with .NET recently to see if I like it as a strongly typed web framework. One issue I ran into
though, was that the naming conventions with Entity Framework leverage pascal case. I appreciate that this closely
matches the code, but I have been using Postgres for my database of choice lately and case-sensitive naming is a pain
in that environment.

I found like-minded individuals discussing options in [GitHub issues](https://github.com/aspnet/EntityFrameworkCore/issues/5159#issuecomment-376112332)
and [blogs](https://andrewlock.net/customising-asp-net-core-identity-ef-core-naming-conventions-for-postgresql/),
and really appreciated the insights. So that I don't have to count on finding those posts again, below is the `DbContext`
I put together to convert naming conventions from pascal case to snake case. Along with converting to snake case, I
also removed the `asp_net_` prefix from the converted .NET Identity tables. This code is almost entirely copy-pasted from the
other sources, so I'm not trying to take credit for it, really just documenting it for myself.

```cs
using Microsoft.EntityFrameworkCore;
using System.Collections.Generic;

namespace App.Data
{
    public class AppDbContext : DbContext
    {
        public AppDbContext(DbContextOptions<AppDbContext> options): base(options)
        {
        }

        protected override void OnModelCreating(ModelBuilder builder)
        {
            base.OnModelCreating(builder);

            // Npgsql comes with a it's own translator, making conversion easy
            var mapper = new Npgsql.NameTranslation.NpgsqlSnakeCaseNameTranslator();

            foreach(var entity in builder.Model.GetEntityTypes())
            {
                entity.Relational().TableName = mapper.TranslateTypeName(entity.Relational().TableName);

                foreach(var property in entity.GetProperties())
                {
                    property.Relational().ColumnName = mapper.TranslateMemberName(property.Name);
                }

                foreach(var key in entity.GetKeys())
                {
                    key.Relational().Name = mapper.TranslateMemberName(key.Relational().Name);
                }

                foreach(var key in entity.GetForeignKeys())
                {
                    key.Relational().Name = mapper.TranslateMemberName(key.Relational().Name);
                }

                foreach(var index in entity.GetIndexes())
                {
                    index.Relational().Name = mapper.TranslateMemberName(index.Relational().Name);
                }

                // Drop the 'asp_net_' prefix from the Identity tables
                if (entity.Relational().TableName.StartsWith("asp_net_"))
                {
                    entity.Relational().TableName = entity.Relational().TableName.Replace("asp_net_", string.Empty);
                }
            }
        }
    }
}
```
